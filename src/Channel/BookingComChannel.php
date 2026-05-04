<?php

namespace App\Channel;

use App\Entity\ChannelConnection;
use Psr\Log\LoggerInterface;

class BookingComChannel implements ChannelManagerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function syncListings(ChannelConnection $connection): array
    {
        $this->logger->info('Booking.com syncListings - not implemented (requires connectivity partner access)');

        return [];
    }

    public function syncAvailability(ChannelConnection $connection, array $availability): void
    {
        $this->logger->info('Booking.com syncAvailability - not implemented (requires connectivity partner access)');
    }

    public function syncBookings(ChannelConnection $connection): array
    {
        $this->logger->info('Booking.com syncBookings - not implemented (requires connectivity partner access)');

        return [];
    }

    public function syncPrices(ChannelConnection $connection, array $prices): void
    {
        $this->logger->info('Booking.com syncPrices - not implemented (requires connectivity partner access)');
    }
}
