<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class WidgetDataResult
{
    /**
     * @param array{date: string, available: bool, price: int|null}[] $calendar
     */
    public function __construct(
        #[Groups(['widget:read'])]
        public readonly string $lodgingId,
        #[Groups(['widget:read'])]
        public readonly string $lodgingName,
        #[Groups(['widget:read'])]
        public readonly int $basePriceWeek,
        #[Groups(['widget:read'])]
        public readonly int $basePriceWeekend,
        #[Groups(['widget:read'])]
        public readonly int $cleaningFee,
        #[Groups(['widget:read'])]
        public readonly int $touristTaxPerPerson,
        #[Groups(['widget:read'])]
        public readonly ?int $minStay,
        #[Groups(['widget:read'])]
        public readonly ?int $maxStay,
        #[Groups(['widget:read'])]
        public readonly int $capacity,
        #[Groups(['widget:read'])]
        public readonly string $currency,
        #[Groups(['widget:read'])]
        public readonly string $cancellationPolicy,
        #[Groups(['widget:read'])]
        public readonly string $checkinTime,
        #[Groups(['widget:read'])]
        public readonly string $checkoutTime,
        #[Groups(['widget:read'])]
        public readonly array $calendar,
    ) {
    }
}
