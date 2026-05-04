<?php

namespace App\Channel;

use App\Entity\ChannelConnection;
use Psr\Log\LoggerInterface;

class AirbnbChannel implements ChannelManagerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function syncListings(ChannelConnection $connection): array
    {
        $this->logger->info('Airbnb syncListings - not implemented (requires partner API access)');

        return [];
    }

    public function syncAvailability(ChannelConnection $connection, array $availability): void
    {
        $this->logger->info('Airbnb syncAvailability - not implemented (requires partner API access)');
    }

    public function syncBookings(ChannelConnection $connection): array
    {
        $this->logger->info('Airbnb syncBookings - not implemented (requires partner API access)');

        return [];
    }

    public function syncPrices(ChannelConnection $connection, array $prices): void
    {
        $this->logger->info('Airbnb syncPrices - not implemented (requires partner API access)');
    }
}
