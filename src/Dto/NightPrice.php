<?php

namespace App\Dto;

class NightPrice
{
    public function __construct(
        public readonly \DateTimeImmutable $date,
        public readonly int $price,
        public readonly string $source,
    ) {
    }
}
