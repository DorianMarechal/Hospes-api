<?php

namespace App\Dto;

use Symfony\Component\Uid\Uuid;

class AvailabilitySearchResult
{
    public function __construct(
        public readonly Uuid $lodgingId,
        public readonly string $name,
        public readonly string $type,
        public readonly string $city,
        public readonly ?string $region,
        public readonly string $country,
        public readonly int $capacity,
        public readonly int $basePriceWeek,
        public readonly int $basePriceWeekend,
        public readonly ?string $averageRating,
        public readonly ?int $reviewCount,
    ) {
    }
}
