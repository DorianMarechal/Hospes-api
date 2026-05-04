<?php

namespace App\Repository;

use App\Entity\PropertyOwner;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyOwner>
 */
class PropertyOwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyOwner::class);
    }

    public function findByUser(User $user): ?PropertyOwner
    {
        return $this->findOneBy(['user' => $user]);
    }
}
