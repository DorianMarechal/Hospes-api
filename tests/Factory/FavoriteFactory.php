<?php

namespace App\Tests\Factory;

use App\Entity\Favorite;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Favorite>
 */
final class FavoriteFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Favorite::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new(['roles' => ['ROLE_CUSTOMER']]),
            'lodging' => LodgingFactory::new(),
            'createdAt' => new \DateTimeImmutable(),
        ];
    }
}
