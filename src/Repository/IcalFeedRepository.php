<?php

namespace App\Repository;

use App\Entity\IcalFeed;
use App\Entity\Lodging;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IcalFeed>
 */
class IcalFeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcalFeed::class);
    }

    /**
     * @return IcalFeed[]
     */
    public function findByLodging(Lodging $lodging): array
    {
        return $this->findBy(['lodging' => $lodging]);
    }
}
