<?php

namespace App\Tests\Factory;

use App\Entity\Conversation;
use App\Enum\ConversationStatus;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Conversation>
 */
final class ConversationFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Conversation::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();
        $hostProfile = HostProfileFactory::createOne();

        return [
            'lodging' => LodgingFactory::new(['host' => $hostProfile]),
            'customer' => UserFactory::new(['roles' => ['ROLE_CUSTOMER']]),
            'host' => $hostProfile->getUser(),
            'status' => ConversationStatus::OPEN,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }
}
