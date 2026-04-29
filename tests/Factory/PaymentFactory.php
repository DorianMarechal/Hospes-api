<?php

namespace App\Tests\Factory;

use App\Entity\Payment;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Payment>
 */
final class PaymentFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Payment::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'booking' => BookingFactory::new(),
            'amount' => 35600,
            'type' => PaymentType::BOOKING,
            'method' => PaymentMethod::CARD,
            'status' => PaymentStatus::SUCCEEDED,
            'provider' => 'stripe',
            'providerTransactionId' => 'txn_'.bin2hex(random_bytes(8)),
            'createdAt' => new \DateTimeImmutable(),
        ];
    }
}
