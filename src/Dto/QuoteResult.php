<?php

namespace App\Dto;

class QuoteResult
{
    /**
     * @param NightPrice[] $nights
     */
    public function __construct(
        public readonly array $nights,
        public readonly int $nightsTotal,
        public readonly int $cleaningFee,
        public readonly int $touristTaxTotal,
        public readonly int $depositAmount,
        public readonly int $totalPrice,
    ) {
    }
}
