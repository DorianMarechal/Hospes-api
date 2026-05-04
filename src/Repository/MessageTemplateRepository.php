<?php

namespace App\Repository;

use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\MessageTemplate;
use App\Enum\MessageTemplateTrigger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageTemplate>
 */
class MessageTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageTemplate::class);
    }

    /**
     * @return MessageTemplate[]
     */
    public function findByHostProfile(HostProfile $hostProfile): array
    {
        return $this->createQueryBuilder('mt')
            ->andWhere('mt.hostProfile = :host')
            ->setParameter('host', $hostProfile)
            ->orderBy('mt.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MessageTemplate[]
     */
    public function findEnabledByTrigger(MessageTemplateTrigger $trigger): array
    {
        return $this->createQueryBuilder('mt')
            ->andWhere('mt.triggerType = :trigger')
            ->andWhere('mt.enabled = true')
            ->setParameter('trigger', $trigger)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MessageTemplate[]
     */
    public function findEnabledByTriggerAndLodging(MessageTemplateTrigger $trigger, Lodging $lodging): array
    {
        return $this->createQueryBuilder('mt')
            ->andWhere('mt.triggerType = :trigger')
            ->andWhere('mt.enabled = true')
            ->andWhere('mt.lodging IS NULL OR mt.lodging = :lodging')
            ->andWhere('mt.hostProfile = :host')
            ->setParameter('trigger', $trigger)
            ->setParameter('lodging', $lodging)
            ->setParameter('host', $lodging->getHost())
            ->getQuery()
            ->getResult();
    }
}
