<?php

namespace App\Service;

use App\Entity\Lodging;
use App\Enum\BookingStatus;
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
        $revenue = 0;
        $bookingsCount = 0;
        $occupiedNights = 0;
        $totalDays = max(1, $from->diff($to)->days);
        $totalNights = $totalDays * \count($lodgings);

        foreach ($lodgings as $lodging) {
            $bookings = $this->bookingRepository->findByLodging($lodging);

            foreach ($bookings as $booking) {
                if (BookingStatus::CANCELLED === $booking->getStatus()) {
                    continue;
                }

                if (BookingStatus::PENDING === $booking->getStatus()) {
                    continue;
                }

                $checkin = $booking->getCheckin();
                $checkout = $booking->getCheckout();

                if ($checkout <= $from || $checkin >= $to) {
                    continue;
                }

                ++$bookingsCount;
                $revenue += $booking->getTotalPrice() ?? 0;

                $overlapStart = $checkin > $from ? $checkin : $from;
                $overlapEnd = $checkout < $to ? $checkout : $to;
                $occupiedNights += $overlapStart->diff($overlapEnd)->days;
            }
        }

        $occupancyRate = $totalNights > 0 ? round($occupiedNights / $totalNights * 100, 2) : 0.0;
        $revpar = $totalNights > 0 ? (int) round($revenue / $totalNights) : 0;

        return [
            'revenue' => $revenue,
            'bookingsCount' => $bookingsCount,
            'occupiedNights' => $occupiedNights,
            'totalNights' => $totalNights,
            'occupancyRate' => $occupancyRate,
            'revpar' => $revpar,
        ];
    }
}
