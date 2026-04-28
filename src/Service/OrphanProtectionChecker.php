<?php

namespace App\Service;

use App\Entity\BlockedDate;
use App\Entity\Booking;
use App\Entity\Lodging;
use App\Entity\Season;
use App\Enum\BookingStatus;
use Symfony\Component\Uid\Uuid;

class OrphanProtectionChecker
{
    /**
     * @param Booking[]     $existingBookings
     * @param BlockedDate[] $blockedDates
     * @param Season[]      $seasons
     */
    public function check(
        Lodging $lodging,
        \DateTimeImmutable $checkin,
        \DateTimeImmutable $checkout,
        array $existingBookings,
        array $blockedDates,
        array $seasons,
        ?Uuid $excludeBookingId = null,
    ): void {
        if (!$lodging->isOrphanProtection()) {
            return;
        }

        $minStay = $this->resolveMinStay($lodging, $checkin, $checkout, $seasons);
        $occupiedPeriods = $this->collectOccupiedPeriods($existingBookings, $blockedDates, $excludeBookingId);

        usort($occupiedPeriods, fn (array $a, array $b) => $a['start'] <=> $b['start']);

        $gapBefore = $this->findGapBefore($checkin, $occupiedPeriods);
        $gapAfter = $this->findGapAfter($checkout, $occupiedPeriods);

        $createsOrphanBefore = null !== $gapBefore && $gapBefore > 0 && $gapBefore < $minStay;
        $createsOrphanAfter = null !== $gapAfter && $gapAfter > 0 && $gapAfter < $minStay;

        if (!$createsOrphanBefore && !$createsOrphanAfter) {
            return;
        }

        $fillsExactGap = 0 === $gapBefore && 0 === $gapAfter;
        if ($fillsExactGap) {
            return;
        }

        throw new \InvalidArgumentException("This booking would create an orphan period (gap shorter than $minStay nights)");
    }

    /**
     * @param Season[] $seasons
     */
    private function resolveMinStay(
        Lodging $lodging,
        \DateTimeImmutable $checkin,
        \DateTimeImmutable $checkout,
        array $seasons,
    ): int {
        $minStay = $lodging->getMinStay() ?? 1;

        foreach ($seasons as $season) {
            if ($checkin < $season->getEndDate() && $checkout > $season->getStartDate()) {
                $seasonMin = $season->getMinStay();
                if (null !== $seasonMin && $seasonMin > $minStay) {
                    $minStay = $seasonMin;
                }
            }
        }

        return $minStay;
    }

    /**
     * @param Booking[]     $bookings
     * @param BlockedDate[] $blockedDates
     *
     * @return array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}>
     */
    private function collectOccupiedPeriods(array $bookings, array $blockedDates, ?Uuid $excludeBookingId): array
    {
        $periods = [];

        foreach ($bookings as $booking) {
            if (null !== $excludeBookingId && null !== $booking->getId() && $booking->getId()->equals($excludeBookingId)) {
                continue;
            }
            if (BookingStatus::CANCELLED === $booking->getStatus()) {
                continue;
            }
            if (BookingStatus::PENDING === $booking->getStatus() && $booking->getExpiresAt() < new \DateTimeImmutable()) {
                continue;
            }

            $periods[] = ['start' => $booking->getCheckin(), 'end' => $booking->getCheckout()];
        }

        foreach ($blockedDates as $blocked) {
            $periods[] = ['start' => $blocked->getStartDate(), 'end' => $blocked->getEndDate()];
        }

        return $periods;
    }

    /**
     * @param array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}> $occupiedPeriods
     */
    private function findGapBefore(\DateTimeImmutable $checkin, array $occupiedPeriods): ?int
    {
        $nearestEnd = null;

        foreach ($occupiedPeriods as $period) {
            if ($period['end'] <= $checkin) {
                if (null === $nearestEnd || $period['end'] > $nearestEnd) {
                    $nearestEnd = $period['end'];
                }
            }
        }

        if (null === $nearestEnd) {
            return null;
        }

        return $nearestEnd->diff($checkin)->days;
    }

    /**
     * @param array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}> $occupiedPeriods
     */
    private function findGapAfter(\DateTimeImmutable $checkout, array $occupiedPeriods): ?int
    {
        $nearestStart = null;

        foreach ($occupiedPeriods as $period) {
            if ($period['start'] >= $checkout) {
                if (null === $nearestStart || $period['start'] < $nearestStart) {
                    $nearestStart = $period['start'];
                }
            }
        }

        if (null === $nearestStart) {
            return null;
        }

        return $checkout->diff($nearestStart)->days;
    }
}
