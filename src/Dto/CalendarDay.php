<?php

namespace App\Dto;

class CalendarDay
{
    public function __construct(
        public readonly string $date,
        public readonly string $status,
        public readonly ?string $bookingId = null,
        public readonly ?string $bookingReference = null,
        public readonly ?string $blockedDateId = null,
        public readonly ?string $reason = null,
    ) {
    }
}
