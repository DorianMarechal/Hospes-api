<?php

namespace App\Tests\Unit\Serializer;

use App\Entity\Booking;
use App\Entity\BookingNight;
use App\Entity\Deposit;
use App\Entity\Lodging;
use App\Entity\Payment;
use App\Serializer\MoneyJsonNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyJsonNormalizerTest extends TestCase
{
    private MoneyJsonNormalizer $normalizer;
    private NormalizerInterface $inner;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(NormalizerInterface::class);
        $this->normalizer = new MoneyJsonNormalizer($this->inner);
    }

    // -------------------------------------------------------------------------
    // Booking — all five money fields wrapped with EUR (hardcoded)
    // -------------------------------------------------------------------------

    public function testBookingMoneyFieldsAreWrappedWithAmountAndEurCurrency(): void
    {
        $booking = new Booking();
        $booking->setNightsTotal(20000);
        $booking->setCleaningFee(5000);
        $booking->setTouristTaxTotal(300);
        $booking->setDepositAmount(10000);
        $booking->setTotalPrice(25300);

        $innerResult = [
            'nightsTotal' => 20000,
            'cleaningFee' => 5000,
            'touristTaxTotal' => 300,
            'depositAmount' => 10000,
            'totalPrice' => 25300,
            'reference' => 'HOS-AABB-26',
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($booking, 'json', []);

        $this->assertSame(['amount' => 20000, 'currency' => 'EUR'], $result['nightsTotal']);
        $this->assertSame(['amount' => 5000,  'currency' => 'EUR'], $result['cleaningFee']);
        $this->assertSame(['amount' => 300,   'currency' => 'EUR'], $result['touristTaxTotal']);
        $this->assertSame(['amount' => 10000, 'currency' => 'EUR'], $result['depositAmount']);
        $this->assertSame(['amount' => 25300, 'currency' => 'EUR'], $result['totalPrice']);
    }

    public function testBookingNonMoneyFieldPassesThroughUnchanged(): void
    {
        $booking = new Booking();

        $innerResult = [
            'nightsTotal' => 10000,
            'totalPrice' => 10000,
            'reference' => 'HOS-XXYY-26',
            'numberOfNights' => 5,
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($booking, 'json', []);

        // Non-money fields must be untouched
        $this->assertSame('HOS-XXYY-26', $result['reference']);
        $this->assertSame(5, $result['numberOfNights']);
    }

    public function testBookingMoneyFieldSkippedWhenValueIsNotAnInteger(): void
    {
        // If inner normalizer already returned a non-int (e.g. null or string),
        // the normalizer must leave the value as-is rather than wrapping it.
        $booking = new Booking();

        $innerResult = [
            'nightsTotal' => null,
            'totalPrice' => '25300',  // string — must not wrap
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($booking, 'json', []);

        $this->assertNull($result['nightsTotal']);
        $this->assertSame('25300', $result['totalPrice']);
    }

    public function testBookingMoneyFieldAbsentInInnerResultIsNotAddedByNormalizer(): void
    {
        // If a money field is not present in the inner result (not serialized
        // because of a group restriction), the normalizer must not inject it.
        $booking = new Booking();

        $innerResult = [
            'totalPrice' => 10000,
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($booking, 'json', []);

        $this->assertArrayNotHasKey('nightsTotal', $result);
        $this->assertSame(['amount' => 10000, 'currency' => 'EUR'], $result['totalPrice']);
    }

    // -------------------------------------------------------------------------
    // MoneyJsonNormalizer always uses EUR (hardcoded) regardless of entity currency
    // -------------------------------------------------------------------------

    public function testMoneyJsonNormalizerAlwaysUsesHardcodedEurCurrency(): void
    {
        // Unlike MoneyNormalizer, MoneyJsonNormalizer never reads getCurrency().
        // Even if the booking had a different currency (e.g. USD), it wraps with EUR.
        $booking = new Booking();
        $booking->setCurrency('USD');

        $innerResult = [
            'nightsTotal' => 18000,
            'totalPrice' => 18000,
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($booking, 'json', []);

        // Always EUR — hardcoded in MoneyJsonNormalizer::DEFAULT_CURRENCY
        $this->assertSame(['amount' => 18000, 'currency' => 'EUR'], $result['nightsTotal']);
        $this->assertSame(['amount' => 18000, 'currency' => 'EUR'], $result['totalPrice']);
    }

    // -------------------------------------------------------------------------
    // BookingNight — price field wrapped
    // -------------------------------------------------------------------------

    public function testBookingNightPriceFieldIsWrapped(): void
    {
        $night = new BookingNight();
        $night->setPrice(8000);

        $innerResult = [
            'price' => 8000,
            'source' => 'season',
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($night, 'json', []);

        $this->assertSame(['amount' => 8000, 'currency' => 'EUR'], $result['price']);
        $this->assertSame('season', $result['source']);
    }

    // -------------------------------------------------------------------------
    // Payment — amount field wrapped
    // -------------------------------------------------------------------------

    public function testPaymentAmountFieldIsWrapped(): void
    {
        $payment = new Payment();
        $payment->setAmount(50000);

        $innerResult = [
            'amount' => 50000,
            'provider' => 'stripe',
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($payment, 'json', []);

        $this->assertSame(['amount' => 50000, 'currency' => 'EUR'], $result['amount']);
        $this->assertSame('stripe', $result['provider']);
    }

    // -------------------------------------------------------------------------
    // Deposit — amount and retainedAmount both wrapped
    // -------------------------------------------------------------------------

    public function testDepositAmountAndRetainedAmountBothWrapped(): void
    {
        $deposit = new Deposit();
        $deposit->setAmount(20000);
        $deposit->setRetainedAmount(5000);

        $innerResult = [
            'amount' => 20000,
            'retainedAmount' => 5000,
            'status' => 'held',
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($deposit, 'json', []);

        $this->assertSame(['amount' => 20000, 'currency' => 'EUR'], $result['amount']);
        $this->assertSame(['amount' => 5000,  'currency' => 'EUR'], $result['retainedAmount']);
        $this->assertSame('held', $result['status']);
    }

    public function testDepositRetainedAmountZeroIsStillWrapped(): void
    {
        $deposit = new Deposit();
        $deposit->setAmount(15000);
        $deposit->setRetainedAmount(0);

        $innerResult = [
            'amount' => 15000,
            'retainedAmount' => 0,
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($deposit, 'json', []);

        $this->assertSame(['amount' => 0, 'currency' => 'EUR'], $result['retainedAmount']);
    }

    // -------------------------------------------------------------------------
    // Non-registered entity passes through untouched
    // -------------------------------------------------------------------------

    public function testNonRegisteredEntityPassesThroughUnchanged(): void
    {
        // Lodging is not in MONEY_FIELDS — result must be returned as-is
        $lodging = new Lodging();

        $innerResult = [
            'name' => 'Le Mas Provençal',
            'basePriceWeek' => 12000,
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($lodging, 'json', []);

        // basePriceWeek must NOT be wrapped — Lodging is not in MONEY_FIELDS
        $this->assertSame(12000, $result['basePriceWeek']);
        $this->assertSame('Le Mas Provençal', $result['name']);
    }

    // -------------------------------------------------------------------------
    // Non-array result from inner normalizer passes through untouched
    // -------------------------------------------------------------------------

    public function testNonArrayResultFromInnerNormalizerPassesThroughUnchanged(): void
    {
        $booking = new Booking();

        $this->inner->method('normalize')->willReturn('raw-string');

        $result = $this->normalizer->normalize($booking, 'json', []);

        $this->assertSame('raw-string', $result);
    }

    // -------------------------------------------------------------------------
    // supportsNormalization and getSupportedTypes delegate to inner
    // -------------------------------------------------------------------------

    public function testSupportsNormalizationDelegatesToInner(): void
    {
        $booking = new Booking();

        $this->inner
            ->method('supportsNormalization')
            ->with($booking, 'json', [])
            ->willReturn(true);

        $this->assertTrue($this->normalizer->supportsNormalization($booking, 'json', []));
    }

    public function testSupportsNormalizationReturnsFalseWhenInnerReturnsFalse(): void
    {
        $booking = new Booking();

        $this->inner
            ->method('supportsNormalization')
            ->willReturn(false);

        $this->assertFalse($this->normalizer->supportsNormalization($booking, 'json', []));
    }

    public function testGetSupportedTypesDelegatesToInner(): void
    {
        $supported = [Booking::class => true, Payment::class => true];

        $this->inner
            ->method('getSupportedTypes')
            ->with('json')
            ->willReturn($supported);

        $this->assertSame($supported, $this->normalizer->getSupportedTypes('json'));
    }
}
