<?php

namespace App\Repository;

use App\Entity\ChannelBooking;
use App\Enum\Channel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChannelBooking>
 */
class ChannelBookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChannelBooking::class);
    }

    public function findByExternalId(Channel $channel, string $externalId): ?ChannelBooking
    {
        return $this->findOneBy([
            'channel' => $channel,
            'externalReservationId' => $externalId,
        ]);
    }
}
