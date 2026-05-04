<?php

namespace App\Message;

final readonly class DispatchAutomatedMessageMessage
{
    public function __construct(
        public string $templateId,
        public string $bookingId,
    ) {
    }
}
