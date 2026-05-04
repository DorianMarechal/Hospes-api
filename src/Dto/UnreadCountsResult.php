<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\UnreadCountsProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/me/unread-counts',
            security: "is_granted('ROLE_USER')",
            provider: UnreadCountsProvider::class,
        ),
    ],
)]
class UnreadCountsResult
{
    public int $unreadNotifications = 0;
    public int $unreadMessages = 0;
}
