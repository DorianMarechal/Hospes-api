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
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
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
            if ($user->getPassword()) {
                $user->setPassword($this->hasher->hashPassword($user, $user->getPassword()));
            }
        });
    }
}
