<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\LodgingStatsProvider;
use App\State\MyStatsProvider;

#[ApiResource(
    shortName: 'Stats',
    operations: [
        new Get(
            uriTemplate: '/me/stats',
            security: "is_granted('ROLE_HOST')",
            provider: MyStatsProvider::class,
        ),
        new Get(
            uriTemplate: '/me/lodgings/{lodgingId}/stats',
            security: "is_granted('ROLE_HOST')",
            provider: LodgingStatsProvider::class,
        ),
    ],
)]
class StatsResult
{
    public function __construct(
        public readonly int $revenue = 0,
        public readonly int $bookingsCount = 0,
        public readonly int $occupiedNights = 0,
        public readonly int $totalNights = 0,
        public readonly float $occupancyRate = 0.0,
        public readonly int $revpar = 0,
    ) {
    }
}
