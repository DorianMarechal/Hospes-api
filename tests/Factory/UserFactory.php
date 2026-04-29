<?php

namespace App\Tests\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    private static ?UserPasswordHasherInterface $hasher = null;

    public static function setPasswordHasher(UserPasswordHasherInterface $hasher): void
    {
        self::$hasher = $hasher;
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'email' => self::faker()->unique()->safeEmail(),
            'password' => 'password',
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'roles' => ['ROLE_CUSTOMER'],
            'isActive' => true,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            if (self::$hasher && $user->getPassword()) {
                $user->setPassword(self::$hasher->hashPassword($user, $user->getPassword()));
            }
        });
    }
}
