<?php

namespace App\Repository;

use App\Entity\HostProfile;
use App\Entity\PromotionCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromotionCode>
 */
class PromotionCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromotionCode::class);
    }

    /**
     * @return PromotionCode[]
     */
    public function findByHost(HostProfile $hostProfile): array
    {
        return $this->findBy(['hostProfile' => $hostProfile], ['createdAt' => 'DESC']);
    }

    public function findByCode(string $code): ?PromotionCode
    {
        return $this->findOneBy(['code' => strtoupper($code)]);
    }
}
