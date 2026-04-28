<?php

namespace App\Repository;

use App\Entity\BlockedDate;
use App\Entity\Lodging;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlockedDate>
 */
class BlockedDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlockedDate::class);
    }

    /**
     * @return BlockedDate[]
     */
    public function findByLodging(Lodging $lodging): array
    {
        return $this->findBy(['lodging' => $lodging]);
    }
}
