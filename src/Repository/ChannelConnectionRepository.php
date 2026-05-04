<?php

namespace App\Repository;

use App\Entity\ChannelConnection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChannelConnection>
 */
class ChannelConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChannelConnection::class);
    }

    /**
     * @return ChannelConnection[]
     */
    public function findAllActive(): array
    {
        return $this->findAll();
    }
}
