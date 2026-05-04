<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class DirectBookingResult
{
    public function __construct(
        #[Groups(['direct_booking:read'])]
        public readonly string $bookingId,
        #[Groups(['direct_booking:read'])]
        public readonly string $reference,
        #[Groups(['direct_booking:read'])]
        public readonly string $guestPortalToken,
        #[Groups(['direct_booking:read'])]
        public readonly ?string $stripeCheckoutUrl,
        #[Groups(['direct_booking:read'])]
        public readonly int $totalPrice,
        #[Groups(['direct_booking:read'])]
        public readonly string $currency,
        #[Groups(['direct_booking:read'])]
        public readonly string $status,
    ) {
    }
}
