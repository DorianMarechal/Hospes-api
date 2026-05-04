<?php

namespace App\Controller;

use App\Dto\WidgetDataResult;
use App\Entity\PriceOverride;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use App\Service\AvailabilityResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class BookingWidgetController
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
        private AvailabilityResolver $availabilityResolver,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/lodgings/{lodgingId}/booking-widget', methods: ['GET'])]
    public function __invoke(string $lodgingId, Request $request): JsonResponse
    {
        $lodging = $this->lodgingRepository->find($lodgingId);
        if (null === $lodging || !$lodging->getIsActive()) {
            throw new NotFoundHttpException('Lodging not found.');
        }

        $months = min(12, max(1, (int) $request->query->get('months', '3')));
        $from = new \DateTimeImmutable('today');
        $to = $from->modify(\sprintf('+%d months', $months));

        $bookings = $this->bookingRepository->findBy(['lodging' => $lodging]);
        $blockedDates = $this->blockedDateRepository->findByLodging($lodging);
        $priceOverrides = $lodging->getPriceOverrides()->toArray();
        $seasons = $lodging->getSeasons()->toArray();

        $calendar = [];
        $current = $from;

        while ($current < $to) {
            $dateStr = $current->format('Y-m-d');
            $nextDay = $current->modify('+1 day');
            $available = $this->availabilityResolver->isAvailable($lodging, $current, $nextDay, $bookings, $blockedDates, null);

            $price = $this->resolvePrice($lodging, $current, $seasons, $priceOverrides);

            $calendar[] = [
                'date' => $dateStr,
                'available' => $available,
                'price' => $price,
            ];

            $current = $nextDay;
        }

        $result = new WidgetDataResult(
            lodgingId: (string) $lodging->getId(),
            lodgingName: $lodging->getName() ?? '',
            basePriceWeek: $lodging->getBasePriceWeek() ?? 0,
            basePriceWeekend: $lodging->getBasePriceWeekend() ?? 0,
            cleaningFee: $lodging->getCleaningFee() ?? 0,
            touristTaxPerPerson: $lodging->getTouristTaxPerPerson() ?? 0,
            minStay: $lodging->getMinStay(),
            maxStay: $lodging->getMaxStay(),
            capacity: $lodging->getCapacity() ?? 0,
            currency: $lodging->getCurrency(),
            cancellationPolicy: null !== $lodging->getCancellationPolicy() ? $lodging->getCancellationPolicy()->value : 'flexible',
            checkinTime: $lodging->getCheckinTime()?->format('H:i') ?? '15:00',
            checkoutTime: $lodging->getCheckoutTime()?->format('H:i') ?? '11:00',
            calendar: $calendar,
        );

        $json = $this->serializer->serialize($result, 'json', ['groups' => ['widget:read']]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @param \App\Entity\Season[] $seasons
     * @param PriceOverride[]      $priceOverrides
     */
    private function resolvePrice(mixed $lodging, \DateTimeImmutable $date, array $seasons, array $priceOverrides): int
    {
        $isWeekend = \in_array((int) $date->format('N'), [5, 6], true);

        foreach ($priceOverrides as $override) {
            if ($override->getDate()?->format('Y-m-d') === $date->format('Y-m-d')) {
                return $override->getPrice() ?? 0;
            }
        }

        foreach ($seasons as $season) {
            if ($date >= $season->getStartDate() && $date < $season->getEndDate()) {
                return $isWeekend ? ($season->getPriceWeekend() ?? 0) : ($season->getPriceWeek() ?? 0);
            }
        }

        return $isWeekend ? ($lodging->getBasePriceWeekend() ?? 0) : ($lodging->getBasePriceWeek() ?? 0);
    }
}
