<?php

namespace App\Tests\Factory;

use App\Entity\Deposit;
use App\Enum\DepositStatus;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Deposit>
 */
final class DepositFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Deposit::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'booking' => BookingFactory::new(),
            'amount' => 30000,
            'status' => DepositStatus::HELD,
            'retainedAmount' => 0,
            'createdAt' => new \DateTimeImmutable(),
        ];
    }
}
