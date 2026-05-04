<?php

namespace App\Tests\Factory;

use App\Entity\BookingModificationRequest;
use App\Enum\ModificationRequestStatus;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<BookingModificationRequest>
 */
final class BookingModificationRequestFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return BookingModificationRequest::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'booking' => BookingFactory::new(),
            'requestedBy' => UserFactory::new(),
            'proposedCheckin' => new \DateTimeImmutable('+14 days'),
            'proposedCheckout' => new \DateTimeImmutable('+17 days'),
            'proposedNumberOfNights' => 3,
            'proposedNightsTotal' => 30000,
            'proposedCleaningFee' => 5000,
            'proposedTouristTaxTotal' => 600,
            'proposedDepositAmount' => 30000,
            'proposedTotalPrice' => 35600,
            'status' => ModificationRequestStatus::PENDING,
            'expiresAt' => $now->modify('+48 hours'),
            'createdAt' => $now,
        ];
    }
}
