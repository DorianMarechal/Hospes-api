<?php

namespace App\Tests\Factory;

use App\Entity\Review;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Review>
 */
final class ReviewFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Review::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'booking' => BookingFactory::new(),
            'lodging' => LodgingFactory::new(),
            'customer' => UserFactory::new(['roles' => ['ROLE_CUSTOMER']]),
            'rating' => self::faker()->numberBetween(1, 5),
            'comment' => self::faker()->paragraph(),
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }
}
