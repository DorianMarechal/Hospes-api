<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\InsuranceClaim;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InsuranceClaim>
 */
class InsuranceClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InsuranceClaim::class);
    }

    public function findByBooking(Booking $booking): ?InsuranceClaim
    {
        return $this->findOneBy(['booking' => $booking]);
    }
}
