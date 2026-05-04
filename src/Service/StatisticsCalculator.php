<?php

namespace App\Service;

use App\Entity\Lodging;
use App\Repository\BookingRepository;

class StatisticsCalculator
{
    public function __construct(
        private BookingRepository $bookingRepository,
    ) {
    }

    /**
     * @param Lodging[] $lodgings
     *
     * @return array{revenue: int, bookingsCount: int, occupiedNights: int, totalNights: int, occupancyRate: float, revpar: int}
     */
    public function calculate(array $lodgings, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $totalDays = max(1, $from->diff($to)->days);
        $totalNights = $totalDays * \count($lodgings);

        $lodgingIds = array_filter(array_map(
            fn (Lodging $l) => $l->getId()?->toRfc4122(),
            $lodgings,
        ));

        if ([] === $lodgingIds) {
            return [
                'revenue' => 0,
                'bookingsCount' => 0,
                'occupiedNights' => 0,
                'totalNights' => $totalNights,
                'occupancyRate' => 0.0,
                'revpar' => 0,
            ];
        }

        $stats = $this->bookingRepository->aggregateStats($lodgingIds, $from, $to);

        $occupancyRate = $totalNights > 0 ? round($stats['occupiedNights'] / $totalNights * 100, 2) : 0.0;
        $revpar = $totalNights > 0 ? (int) round($stats['revenue'] / $totalNights) : 0;

        return [
            'revenue' => $stats['revenue'],
            'bookingsCount' => $stats['bookingsCount'],
            'occupiedNights' => $stats['occupiedNights'],
            'totalNights' => $totalNights,
            'occupancyRate' => $occupancyRate,
            'revpar' => $revpar,
        ];
    }
}
