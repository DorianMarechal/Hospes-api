<?php

namespace App\Tests\Factory;

use App\Entity\IcalFeed;
use App\Enum\IcalDirection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<IcalFeed>
 */
final class IcalFeedFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return IcalFeed::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'lodging' => LodgingFactory::new(),
            'url' => 'https://example.com/calendar.ics',
            'direction' => IcalDirection::IMPORT,
            'createdAt' => new \DateTimeImmutable(),
        ];
    }
}
