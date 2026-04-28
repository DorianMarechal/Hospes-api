<?php

namespace App\Repository;

use App\Entity\Lodging;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return Review[]
     */
    public function findByLodging(Lodging $lodging): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.lodging = :lodging')
            ->setParameter('lodging', $lodging)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Review[]
     */
    public function findByCustomer(User $customer): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
