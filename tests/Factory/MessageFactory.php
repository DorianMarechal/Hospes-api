<?php

namespace App\Tests\Factory;

use App\Entity\Message;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Message>
 */
final class MessageFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Message::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'conversation' => ConversationFactory::new(),
            'sender' => UserFactory::new(),
            'content' => 'Test message content',
            'isRead' => false,
            'createdAt' => new \DateTimeImmutable(),
        ];
    }
}
