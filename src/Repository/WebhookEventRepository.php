<?php

namespace App\Repository;

use App\Entity\WebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebhookEvent>
 */
class WebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookEvent::class);
    }

    public function hasBeenProcessed(string $provider, string $providerEventId): bool
    {
        $count = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.provider = :provider')
            ->andWhere('w.providerEventId = :eventId')
            ->setParameter('provider', $provider)
            ->setParameter('eventId', $providerEventId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
