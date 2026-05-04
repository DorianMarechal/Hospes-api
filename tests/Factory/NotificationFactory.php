<?php

namespace App\Tests\Factory;

use App\Entity\Notification;
use App\Enum\NotificationType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Notification>
 */
final class NotificationFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Notification::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'type' => NotificationType::BOOKING_CONFIRMED,
            'title' => 'New booking',
            'content' => 'A new booking has been confirmed',
            'isRead' => false,
            'createdAt' => new \DateTimeImmutable(),
        ];
    }
}
