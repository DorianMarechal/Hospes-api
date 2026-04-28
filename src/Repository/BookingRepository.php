<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Lodging;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return Booking[]
     */
    public function findByLodging(Lodging $lodging): array
    {
        return $this->findBy(['lodging' => $lodging]);
    }

    /**
     * @return Booking[]
     */
    public function findByCustomer(User $customer): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByReference(string $reference): ?Booking
    {
        return $this->findOneBy(['reference' => $reference]);
    }
}
