<?php

namespace App\Repository;

use App\Entity\AccessCode;
use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessCode>
 */
class AccessCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessCode::class);
    }

    public function findByBooking(Booking $booking): ?AccessCode
    {
        return $this->findOneBy(['booking' => $booking]);
    }
}
