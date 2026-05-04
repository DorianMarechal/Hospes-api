<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class OwnerStatement
{
    public function __construct(
        #[Groups(['owner_statement:read'])]
        public readonly string $month,
        #[Groups(['owner_statement:read'])]
        public readonly string $lodgingName,
        #[Groups(['owner_statement:read'])]
        public readonly string $lodgingId,
        #[Groups(['owner_statement:read'])]
        public readonly int $grossRevenue,
        #[Groups(['owner_statement:read'])]
        public readonly int $commission,
        #[Groups(['owner_statement:read'])]
        public readonly int $netRevenue,
        #[Groups(['owner_statement:read'])]
        public readonly string $currency,
        #[Groups(['owner_statement:read'])]
        public readonly int $bookingCount,
    ) {
    }
}
