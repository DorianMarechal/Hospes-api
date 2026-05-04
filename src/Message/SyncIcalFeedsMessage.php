<?php

namespace App\Message;

final readonly class SyncIcalFeedsMessage
{
    public function __construct(
        public ?string $lodgingId = null,
    ) {
    }
}
