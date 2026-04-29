<?php

namespace App\Tests\Factory;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Enum\CancellationPolicy;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Booking>
 */
final class BookingFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Booking::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();
        $checkin = new \DateTimeImmutable('+7 days');
        $checkout = new \DateTimeImmutable('+10 days');

        return [
            'lodging' => LodgingFactory::new(),
            'customer' => UserFactory::new(),
            'reference' => strtoupper(bin2hex(random_bytes(6))),
            'checkin' => $checkin,
            'checkout' => $checkout,
            'guestsCount' => 2,
            'numberOfNights' => 3,
            'nightsTotal' => 30000,
            'cleaningFee' => 5000,
            'touristTaxTotal' => 600,
            'depositAmount' => 30000,
            'totalPrice' => 35600,
            'cancellationPolicy' => CancellationPolicy::MODERATE,
            'status' => BookingStatus::CONFIRMED,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
    }
}
