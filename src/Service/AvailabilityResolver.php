<?php

namespace App\Service;

use App\Entity\BlockedDate;
use App\Entity\Booking;
use App\Entity\Lodging;
use App\Entity\Season;
use App\Enum\BookingStatus;
use Symfony\Component\Uid\Uuid;

class AvailabilityResolver
{
    private function hasOverlap(
        \DateTimeImmutable $start1,
        \DateTimeImmutable $end1,
        \DateTimeImmutable $start2,
        \DateTimeImmutable $end2,
    ): bool {
        return $start1 < $end2 && $end1 > $start2;
    }

    /**
     * @param Season[] $seasons
     */
    public function validateStayDuration(
        Lodging $lodging,
        \DateTimeImmutable $checkin,
        \DateTimeImmutable $checkout,
        array $seasons,
    ): void {
        $numberOfNights = $checkin->diff($checkout)->days;
        $minStay = $lodging->getMinStay() ?? 1;
        $maxStay = $lodging->getMaxStay();

        foreach ($seasons as $season) {
            $isOverlap = $this->hasOverlap($checkin, $checkout, $season->getStartDate(), $season->getEndDate());

            if ($isOverlap) {
                $seasonMin = $season->getMinStay();

                if (null !== $seasonMin && $seasonMin > $minStay) {
                    $minStay = $seasonMin;
                }
            }
        }

        if ($numberOfNights < $minStay) {
            throw new \InvalidArgumentException("Minimum stay is $minStay nights");
        }

        if (null !== $maxStay && $numberOfNights > $maxStay) {
            throw new \InvalidArgumentException("Maximum stay is $maxStay nights");
        }
    }

    /**
     * @param Booking[]     $existingBookings
     * @param BlockedDate[] $blockedDates
     */
    public function isAvailable(
        Lodging $lodging,
        \DateTimeImmutable $checkin,
        \DateTimeImmutable $checkout,
        array $existingBookings,
        array $blockedDates,
        ?Uuid $excludeBookingId,
    ): bool {
        foreach ($existingBookings as $booking) {
            if (null !== $booking->getId() && $booking->getId()->equals($excludeBookingId)) {
                continue;
            }

            if (BookingStatus::CANCELLED === $booking->getStatus()) {
                continue;
            }

            if (BookingStatus::PENDING === $booking->getStatus() && $booking->getExpiresAt() < new \DateTimeImmutable()) {
                continue;
            }

            if ($this->hasOverlap($checkin, $checkout, $booking->getCheckin(), $booking->getCheckout())) {
                return false;
            }
        }

        foreach ($blockedDates as $blockedDate) {
            if ($this->hasOverlap($checkin, $checkout, $blockedDate->getStartDate(), $blockedDate->getEndDate())) {
                return false;
            }
        }

        return true;
    }
}
