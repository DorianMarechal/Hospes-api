<?php

namespace App\Tests\Factory;

use App\Entity\Season;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Season>
 */
final class SeasonFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Season::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'lodging' => LodgingFactory::new(),
            'name' => 'Haute saison',
            'startDate' => new \DateTimeImmutable('+1 month'),
            'endDate' => new \DateTimeImmutable('+3 months'),
            'priceWeek' => 12000,
            'priceWeekend' => 15000,
            'createdAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ];
    }
}
