<?php

namespace App\Tests\Factory;

use App\Entity\StaffAssignment;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<StaffAssignment>
 */
final class StaffAssignmentFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return StaffAssignment::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'host' => UserFactory::new(['roles' => ['ROLE_HOST']]),
            'staff' => UserFactory::new(['roles' => ['ROLE_STAFF']]),
            'isRevoked' => false,
            'invitationToken' => bin2hex(random_bytes(16)),
            'invitationEmail' => self::faker()->email(),
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }
}
