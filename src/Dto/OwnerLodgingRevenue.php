<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class OwnerLodgingRevenue
{
    public function __construct(
        #[Groups(['owner_revenue:read'])]
        public readonly int $grossRevenue,
        #[Groups(['owner_revenue:read'])]
        public readonly int $commission,
        #[Groups(['owner_revenue:read'])]
        public readonly int $netRevenue,
        #[Groups(['owner_revenue:read'])]
        public readonly string $currency,
        #[Groups(['owner_revenue:read'])]
        public readonly int $bookingCount,
        #[Groups(['owner_revenue:read'])]
        public readonly string $commissionRate,
    ) {
    }
}
