<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * @return Conversation[]
     */
    public function findByCustomer(User $customer): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Conversation[]
     */
    public function findByHost(User $host): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.host = :host')
            ->setParameter('host', $host)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
