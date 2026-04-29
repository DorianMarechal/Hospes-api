<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Payment;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * @return Payment[]
     */
    public function findByBooking(Booking $booking): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.booking = :booking')
            ->setParameter('booking', $booking)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<\Symfony\Component\Uid\Uuid|null> $lodgingIds
     *
     * @return Payment[]
     */
    public function findReceivedByHost(array $lodgingIds): array
    {
        if ([] === $lodgingIds) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->join('p.booking', 'b')
            ->andWhere('b.lodging IN (:lodgingIds)')
            ->andWhere('p.type = :type')
            ->setParameter('lodgingIds', $lodgingIds)
            ->setParameter('type', PaymentType::BOOKING)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasSucceededPayment(Booking $booking): bool
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.booking = :booking')
            ->andWhere('p.type = :type')
            ->andWhere('p.status = :status')
            ->setParameter('booking', $booking)
            ->setParameter('type', PaymentType::BOOKING)
            ->setParameter('status', PaymentStatus::SUCCEEDED)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
