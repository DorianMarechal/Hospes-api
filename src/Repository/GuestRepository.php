<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Guest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Guest>
 */
class GuestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guest::class);
    }

    /**
     * @return Guest[]
     */
    public function findByBooking(Booking $booking): array
    {
        return $this->findBy(['booking' => $booking], ['createdAt' => 'ASC']);
    }
}
