<?php

namespace App\Tests\Unit\Serializer;

use App\Entity\Booking;
use App\Entity\Lodging;
use App\Serializer\MoneyNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizerTest extends TestCase
{
    private MoneyNormalizer $normalizer;
    private NormalizerInterface $inner;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(NormalizerInterface::class);
        $this->normalizer = new MoneyNormalizer($this->inner);
    }

    private function buildBookingWithCurrency(string $currency): Booking
    {
        $booking = new Booking();
        $booking->setCurrency($currency);
        $booking->setNightsTotal(20000);
        $booking->setCleaningFee(5000);
        $booking->setTouristTaxTotal(300);
        $booking->setDepositAmount(10000);
        $booking->setTotalPrice(25300);

        return $booking;
    }

    public function testBookingWithCurrencyEurWrapsMoneyFieldsWithAmountAndCurrency(): void
    {
        $booking = $this->buildBookingWithCurrency('EUR');

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
        $result = $this->normalizer->normalize($booking, 'jsonld', []);

        $this->assertSame(['amount' => 20000, 'currency' => 'EUR'], $result['nightsTotal']);
        $this->assertSame(['amount' => 5000, 'currency' => 'EUR'], $result['cleaningFee']);
        $this->assertSame(['amount' => 300, 'currency' => 'EUR'], $result['touristTaxTotal']);
        $this->assertSame(['amount' => 10000, 'currency' => 'EUR'], $result['depositAmount']);
        $this->assertSame(['amount' => 25300, 'currency' => 'EUR'], $result['totalPrice']);
        // Non-money field passes through unchanged
        $this->assertSame('HOS-AABB-26', $result['reference']);
    }

    public function testBookingWithCurrencyUsdWrapsMoneyFieldsWithUsdCurrency(): void
    {
        $booking = $this->buildBookingWithCurrency('USD');

        $innerResult = [
            'nightsTotal' => 18000,
            'cleaningFee' => 0,
            'touristTaxTotal' => 0,
            'depositAmount' => 0,
            'totalPrice' => 18000,
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($booking, 'jsonld', []);

        $this->assertSame(['amount' => 18000, 'currency' => 'USD'], $result['nightsTotal']);
        $this->assertSame(['amount' => 18000, 'currency' => 'USD'], $result['totalPrice']);
    }

    public function testNonMoneyEntityPassesThroughUnchanged(): void
    {
        // Lodging is not in MONEY_FIELDS — result must be returned as-is
        $lodging = new Lodging();

        $innerResult = [
            'name' => 'Chalet Montagne',
            'basePriceWeek' => 15000,
        ];

        $this->inner->method('normalize')->willReturn($innerResult);

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($lodging, 'jsonld', []);

        // basePriceWeek must NOT be wrapped — Lodging is not in MONEY_FIELDS
        $this->assertSame(15000, $result['basePriceWeek']);
        $this->assertSame('Chalet Montagne', $result['name']);
    }

    public function testSupportsNormalizationDelegatesToInner(): void
    {
        $booking = new Booking();

        $this->inner->method('supportsNormalization')->with($booking, 'jsonld', [])->willReturn(true);

        $this->assertTrue($this->normalizer->supportsNormalization($booking, 'jsonld', []));
    }

    public function testGetSupportedTypesDelegatesToInner(): void
    {
        $this->inner->method('getSupportedTypes')->with('jsonld')->willReturn([Booking::class => true]);

        $this->assertSame([Booking::class => true], $this->normalizer->getSupportedTypes('jsonld'));
    }
}
