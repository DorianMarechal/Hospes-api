<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use App\Enum\CancellationPolicy;

class CancellationPolicyResolver
{
    /**
     * @return array{eligible: bool, refundAmount: int}
     */
    public function resolve(Booking $booking, User $cancelledBy, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        // Host-initiated cancellation: always full refund
        $lodgingHost = $booking->getLodging()?->getHost()?->getUser();
        if (null !== $lodgingHost && $lodgingHost->getId()?->equals($cancelledBy->getId())) {
            return ['eligible' => true, 'refundAmount' => $booking->getTotalPrice() ?? 0];
        }

        $checkin = $booking->getCheckin();
        if (null === $checkin) {
            return ['eligible' => false, 'refundAmount' => 0];
        }

        $policy = $booking->getCancellationPolicy();
        $totalPrice = $booking->getTotalPrice() ?? 0;

        return match ($policy) {
            CancellationPolicy::FLEXIBLE => $this->resolveFlexible($now, $checkin, $totalPrice),
            CancellationPolicy::MODERATE => $this->resolveModerate($now, $checkin, $totalPrice),
            CancellationPolicy::STRICT => ['eligible' => false, 'refundAmount' => 0],
            default => ['eligible' => false, 'refundAmount' => 0],
        };
    }

    /**
     * @return array{eligible: bool, refundAmount: int}
     */
    private function resolveFlexible(\DateTimeImmutable $now, \DateTimeImmutable $checkin, int $totalPrice): array
    {
        $hoursUntilCheckin = ($checkin->getTimestamp() - $now->getTimestamp()) / 3600;

        if ($hoursUntilCheckin > 24) {
            return ['eligible' => true, 'refundAmount' => $totalPrice];
        }

        return ['eligible' => false, 'refundAmount' => 0];
    }

    /**
     * @return array{eligible: bool, refundAmount: int}
     */
    private function resolveModerate(\DateTimeImmutable $now, \DateTimeImmutable $checkin, int $totalPrice): array
    {
        $daysUntilCheckin = ($checkin->getTimestamp() - $now->getTimestamp()) / 86400;

        if ($daysUntilCheckin > 5) {
            return ['eligible' => true, 'refundAmount' => $totalPrice];
        }

        return ['eligible' => false, 'refundAmount' => 0];
    }
}
