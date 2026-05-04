<?php

namespace App\Channel;

use App\Entity\ChannelConnection;

interface ChannelManagerInterface
{
    /**
     * @return array{id: string, name: string}[]
     */
    public function syncListings(ChannelConnection $connection): array;

    /**
     * Push local availability to the channel.
     *
     * @param array{date: string, available: bool}[] $availability
     */
    public function syncAvailability(ChannelConnection $connection, array $availability): void;

    /**
     * Pull reservations from the channel.
     *
     * @return array{
     *     external_id: string,
     *     guest_name: string,
     *     checkin: string,
     *     checkout: string,
     *     guests: int,
     *     status: string,
     *     total_price: int
     * }[]
     */
    public function syncBookings(ChannelConnection $connection): array;

    /**
     * Push prices to the channel.
     *
     * @param array{date: string, price: int}[] $prices
     */
    public function syncPrices(ChannelConnection $connection, array $prices): void;
}
