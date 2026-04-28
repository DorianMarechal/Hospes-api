<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\AvailabilityResult;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use App\Service\AvailabilityResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AvailabilityCheckProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
        private AvailabilityResolver $availabilityResolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AvailabilityResult
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

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

        $nights = $checkin->diff($checkout)->days;

        return new AvailabilityResult(
            lodgingId: $lodging->getId(),
            available: $available,
            checkin: $checkin,
            checkout: $checkout,
            nights: $nights,
        );
    }
}
