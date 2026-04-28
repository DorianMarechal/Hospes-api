<?php

namespace App\Dto;

use Symfony\Component\Uid\Uuid;

class AvailabilityResult
{
    public function __construct(
        public readonly Uuid $lodgingId,
        public readonly bool $available,
        public readonly \DateTimeImmutable $checkin,
        public readonly \DateTimeImmutable $checkout,
        public readonly int $nights,
    ) {
    }
}
