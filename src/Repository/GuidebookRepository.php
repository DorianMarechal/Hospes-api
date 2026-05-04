<?php

namespace App\Repository;

use App\Entity\Guidebook;
use App\Entity\Lodging;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Guidebook>
 */
class GuidebookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guidebook::class);
    }

    public function findByLodging(Lodging $lodging): ?Guidebook
    {
        return $this->findOneBy(['lodging' => $lodging]);
    }
}
