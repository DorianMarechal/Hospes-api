<?php

namespace App\Tests\Factory;

use App\Entity\BlockedDate;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<BlockedDate>
 */
final class BlockedDateFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return BlockedDate::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'lodging' => LodgingFactory::new(),
            'startDate' => new \DateTimeImmutable('+1 month'),
            'endDate' => new \DateTimeImmutable('+1 month +3 days'),
            'reason' => 'Maintenance',
            'source' => 'manual',
            'createdAt' => new \DateTimeImmutable(),
        ];
    }
}
