<?php

namespace App\Service;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;

class PendingBookingCleaner
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function cleanExpired(): int
    {
        $qb = $this->em->createQueryBuilder();
        $qb->update(Booking::class, 'b')
            ->set('b.status', ':cancelled')
            ->set('b.updatedAt', ':now')
            ->where('b.status = :pending')
            ->andWhere('b.expiresAt < :now')
            ->setParameter('cancelled', BookingStatus::CANCELLED)
            ->setParameter('pending', BookingStatus::PENDING)
            ->setParameter('now', new \DateTimeImmutable());

        return $qb->getQuery()->execute();
    }
}
