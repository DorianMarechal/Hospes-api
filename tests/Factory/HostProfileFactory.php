<?php

namespace App\Tests\Factory;

use App\Entity\HostProfile;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<HostProfile>
 */
final class HostProfileFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return HostProfile::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'user' => UserFactory::new(['roles' => ['ROLE_HOST']]),
            'businessName' => self::faker()->company(),
            'legalForm' => 'SAS',
            'country' => 'FR',
            'billingAddress' => self::faker()->streetAddress(),
            'billingCity' => self::faker()->city(),
            'billingPostalCode' => self::faker()->postcode(),
            'billingCountry' => 'FR',
            'timezone' => 'Europe/Paris',
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }
}
