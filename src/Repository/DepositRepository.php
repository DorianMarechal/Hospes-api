<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Deposit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deposit>
 */
class DepositRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deposit::class);
    }

    public function findByBooking(Booking $booking): ?Deposit
    {
        return $this->findOneBy(['booking' => $booking]);
    }
}
