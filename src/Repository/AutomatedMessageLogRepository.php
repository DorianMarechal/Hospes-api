<?php

namespace App\Repository;

use App\Entity\AutomatedMessageLog;
use App\Entity\Booking;
use App\Entity\MessageTemplate;
use App\Enum\MessageChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AutomatedMessageLog>
 */
class AutomatedMessageLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutomatedMessageLog::class);
    }

    public function hasAlreadySent(MessageTemplate $template, Booking $booking, MessageChannel $channel): bool
    {
        return (bool) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.messageTemplate = :template')
            ->andWhere('l.booking = :booking')
            ->andWhere('l.channel = :channel')
            ->setParameter('template', $template)
            ->setParameter('booking', $booking)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
