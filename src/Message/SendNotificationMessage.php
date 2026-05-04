<?php

namespace App\Message;

final readonly class SendNotificationMessage
{
    public function __construct(
        public string $userId,
        public string $type,
        public string $title,
        public string $content,
        public ?string $relatedEntityType = null,
        public ?string $relatedEntityId = null,
    ) {
    }
}
