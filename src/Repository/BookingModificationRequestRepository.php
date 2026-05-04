<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\BookingModificationRequest;
use App\Enum\ModificationRequestStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BookingModificationRequest>
 */
class BookingModificationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookingModificationRequest::class);
    }

    public function findPendingByBooking(Booking $booking): ?BookingModificationRequest
    {
        return $this->createQueryBuilder('mr')
            ->andWhere('mr.booking = :booking')
            ->andWhere('mr.status = :status')
            ->setParameter('booking', $booking)
            ->setParameter('status', ModificationRequestStatus::PENDING)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
