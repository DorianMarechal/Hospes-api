<?php

namespace App\Repository;

use App\Entity\Extra;
use App\Entity\Lodging;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Extra>
 */
class ExtraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Extra::class);
    }

    /**
     * @return Extra[]
     */
    public function findEnabledByLodging(Lodging $lodging): array
    {
        return $this->findBy(['lodging' => $lodging, 'enabled' => true]);
    }
}
