<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Deposit;
use App\Enum\DepositStatus;
use Doctrine\ORM\EntityManagerInterface;

class DepositManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createFromBooking(Booking $booking): ?Deposit
    {
        $amount = $booking->getDepositAmount();
        if (null === $amount || 0 === $amount) {
            return null;
        }

        $deposit = new Deposit();
        $deposit->setBooking($booking);
        $deposit->setAmount($amount);
        $deposit->setStatus(DepositStatus::HELD);
        $deposit->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($deposit);

        return $deposit;
    }

    public function release(Deposit $deposit): void
    {
        $deposit->setStatus(DepositStatus::RELEASED);
        $deposit->setReleasedAt(new \DateTimeImmutable());
        $deposit->setUpdatedAt(new \DateTimeImmutable());
    }

    public function retain(Deposit $deposit, int $retainedAmount, string $reason): void
    {
        $deposit->setRetainedAmount($retainedAmount);
        $deposit->setRetainedReason($reason);
        $deposit->setUpdatedAt(new \DateTimeImmutable());

        if ($retainedAmount >= $deposit->getAmount()) {
            $deposit->setStatus(DepositStatus::FULLY_RETAINED);
        } else {
            $deposit->setStatus(DepositStatus::PARTIALLY_RETAINED);
            $deposit->setReleasedAt(new \DateTimeImmutable());
        }
    }
}
