<?php

namespace App\Tests\Integration\Repository;

use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Repository\PaymentRepository;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\PaymentFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class PaymentRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private PaymentRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(PaymentRepository::class);
    }

    public function testFindByBookingReturnsPaymentsForBooking(): void
    {
        $booking = BookingFactory::createOne();
        $otherBooking = BookingFactory::createOne(['checkin' => new \DateTimeImmutable('+30 days'), 'checkout' => new \DateTimeImmutable('+33 days')]);

        PaymentFactory::createOne(['booking' => $booking]);
        PaymentFactory::createOne(['booking' => $booking]);
        PaymentFactory::createOne(['booking' => $otherBooking]);

        $results = $this->repository->findByBooking($booking->_real());

        $this->assertCount(2, $results);
    }

    public function testFindByBookingReturnsEmptyWhenNoPayments(): void
    {
        $booking = BookingFactory::createOne();

        $results = $this->repository->findByBooking($booking->_real());

        $this->assertCount(0, $results);
    }

    public function testFindByBookingOrderedByCreatedAtDesc(): void
    {
        $booking = BookingFactory::createOne();

        $older = PaymentFactory::createOne(['booking' => $booking, 'createdAt' => new \DateTimeImmutable('2026-01-01')]);
        $newer = PaymentFactory::createOne(['booking' => $booking, 'createdAt' => new \DateTimeImmutable('2026-03-01')]);

        $results = $this->repository->findByBooking($booking->_real());

        $this->assertCount(2, $results);
        $this->assertSame($newer->getId()->toRfc4122(), $results[0]->getId()->toRfc4122());
    }

    public function testFindReceivedByHostReturnsBookingPaymentsForLodgings(): void
    {
        $lodging = LodgingFactory::createOne();
        $booking = BookingFactory::createOne(['lodging' => $lodging]);

        PaymentFactory::createOne(['booking' => $booking, 'type' => PaymentType::BOOKING]);

        $results = $this->repository->findReceivedByHost([$lodging->getId()]);

        $this->assertCount(1, $results);
    }

    public function testFindReceivedByHostExcludesNonBookingType(): void
    {
        $lodging = LodgingFactory::createOne();
        $booking = BookingFactory::createOne(['lodging' => $lodging]);

        PaymentFactory::createOne(['booking' => $booking, 'type' => PaymentType::REFUND]);

        $results = $this->repository->findReceivedByHost([$lodging->getId()]);

        $this->assertCount(0, $results);
    }

    public function testFindReceivedByHostReturnsEmptyForEmptyLodgingIds(): void
    {
        $results = $this->repository->findReceivedByHost([]);

        $this->assertCount(0, $results);
    }

    public function testHasSucceededPaymentReturnsTrue(): void
    {
        $booking = BookingFactory::createOne();
        PaymentFactory::createOne([
            'booking' => $booking,
            'type' => PaymentType::BOOKING,
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $this->assertTrue($this->repository->hasSucceededPayment($booking->_real()));
    }

    public function testHasSucceededPaymentReturnsFalseWhenOnlyFailed(): void
    {
        $booking = BookingFactory::createOne();
        PaymentFactory::createOne([
            'booking' => $booking,
            'type' => PaymentType::BOOKING,
            'status' => PaymentStatus::FAILED,
        ]);

        $this->assertFalse($this->repository->hasSucceededPayment($booking->_real()));
    }

    public function testHasSucceededPaymentReturnsFalseWhenNoPayments(): void
    {
        $booking = BookingFactory::createOne();

        $this->assertFalse($this->repository->hasSucceededPayment($booking->_real()));
    }
}
