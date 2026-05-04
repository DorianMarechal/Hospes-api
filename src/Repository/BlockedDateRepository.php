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

    /**
     * @return BlockedDate[]
     */
    public function findByLodgingInPeriod(Lodging $lodging, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('bd')
            ->andWhere('bd.lodging = :lodging')
            ->andWhere('bd.startDate < :to')
            ->andWhere('bd.endDate > :from')
            ->setParameter('lodging', $lodging)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<Lodging> $lodgings
     *
     * @return array<string, list<BlockedDate>> keyed by lodging UUID
     */
    public function findOverlappingForLodgings(array $lodgings, \DateTimeImmutable $checkin, \DateTimeImmutable $checkout): array
    {
        if ([] === $lodgings) {
            return [];
        }

        $blockedDates = $this->createQueryBuilder('bd')
            ->andWhere('bd.lodging IN (:lodgings)')
            ->andWhere('bd.startDate < :checkout')
            ->andWhere('bd.endDate > :checkin')
            ->setParameter('lodgings', $lodgings)
            ->setParameter('checkin', $checkin)
            ->setParameter('checkout', $checkout)
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($blockedDates as $blocked) {
            $key = $blocked->getLodging()?->getId()?->toRfc4122() ?? '';
            $grouped[$key][] = $blocked;
        }

        return $grouped;
    }
}
