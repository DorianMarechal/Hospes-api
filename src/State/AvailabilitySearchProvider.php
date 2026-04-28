<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\AvailabilitySearchResult;
use App\Enum\LodgingType;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use App\Service\AvailabilityResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AvailabilitySearchProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
        private AvailabilityResolver $availabilityResolver,
    ) {
    }

    /**
     * @return AvailabilitySearchResult[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];

        if (empty($filters['checkin']) || empty($filters['checkout'])) {
            throw new BadRequestHttpException('Query parameters "checkin" and "checkout" are required (format: YYYY-MM-DD)');
        }

        $checkin = \DateTimeImmutable::createFromFormat('Y-m-d', $filters['checkin']);
        $checkout = \DateTimeImmutable::createFromFormat('Y-m-d', $filters['checkout']);

        if (!$checkin || !$checkout) {
            throw new BadRequestHttpException('Invalid date format. Use YYYY-MM-DD');
        }

        $checkin = $checkin->setTime(0, 0);
        $checkout = $checkout->setTime(0, 0);

        if ($checkout <= $checkin) {
            throw new BadRequestHttpException('Checkout must be after checkin');
        }

        $criteria = ['isActive' => true];

        if (!empty($filters['type'])) {
            $type = LodgingType::tryFrom($filters['type']);
            if (!$type) {
                throw new BadRequestHttpException('Invalid lodging type');
            }
            $criteria['type'] = $type;
        }

        if (!empty($filters['city'])) {
            $criteria['city'] = $filters['city'];
        }

        $lodgings = $this->lodgingRepository->findBy($criteria);

        if (!empty($filters['capacity'])) {
            $minCapacity = (int) $filters['capacity'];
            $lodgings = array_filter($lodgings, fn ($l) => $l->getCapacity() >= $minCapacity);
        }

        $results = [];

        foreach ($lodgings as $lodging) {
            $bookings = $this->bookingRepository->findByLodging($lodging);
            $blockedDates = $this->blockedDateRepository->findByLodging($lodging);

            $available = $this->availabilityResolver->isAvailable(
                $lodging,
                $checkin,
                $checkout,
                $bookings,
                $blockedDates,
                null,
            );

            if ($available) {
                $results[] = new AvailabilitySearchResult(
                    lodgingId: $lodging->getId(),
                    name: $lodging->getName(),
                    type: $lodging->getType()->value,
                    city: $lodging->getCity(),
                    region: $lodging->getRegion(),
                    country: $lodging->getCountry(),
                    capacity: $lodging->getCapacity(),
                    basePriceWeek: $lodging->getBasePriceWeek(),
                    basePriceWeekend: $lodging->getBasePriceWeekend(),
                    averageRating: $lodging->getAverageRating(),
                    reviewCount: $lodging->getReviewCount(),
                );
            }
        }

        return $results;
    }
}
