<?php

namespace App\Repository;

use App\Entity\Lodging;
use App\Entity\LodgingTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LodgingTranslation>
 */
class LodgingTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LodgingTranslation::class);
    }

    /**
     * @return LodgingTranslation[]
     */
    public function findByLodging(Lodging $lodging): array
    {
        return $this->findBy(['lodging' => $lodging]);
    }
}
