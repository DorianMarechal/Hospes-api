<?php

namespace App\Tests\Factory;

use App\Entity\Lodging;
use App\Enum\CancellationPolicy;
use App\Enum\LodgingType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Lodging>
 */
final class LodgingFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Lodging::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'host' => HostProfileFactory::new(),
            'name' => self::faker()->words(3, true),
            'type' => LodgingType::GITE,
            'description' => self::faker()->paragraph(),
            'capacity' => self::faker()->numberBetween(2, 10),
            'basePriceWeek' => self::faker()->numberBetween(5000, 20000),
            'basePriceWeekend' => self::faker()->numberBetween(7000, 25000),
            'cleaningFee' => 5000,
            'touristTaxPerPerson' => 100,
            'depositAmount' => 30000,
            'cancellationPolicy' => CancellationPolicy::MODERATE,
            'minStay' => 1,
            'maxStay' => 30,
            'orphanProtection' => false,
            'checkinTime' => new \DateTimeImmutable('15:00'),
            'checkoutTime' => new \DateTimeImmutable('11:00'),
            'address' => self::faker()->streetAddress(),
            'city' => self::faker()->city(),
            'postalCode' => self::faker()->postcode(),
            'country' => 'FR',
            'isActive' => true,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }
}
