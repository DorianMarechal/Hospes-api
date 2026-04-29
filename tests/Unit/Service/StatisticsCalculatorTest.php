<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\Lodging;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Service\StatisticsCalculator;
use PHPUnit\Framework\TestCase;

class StatisticsCalculatorTest extends TestCase
{
    private StatisticsCalculator $calculator;
    private BookingRepository $bookingRepo;

    protected function setUp(): void
    {
        $this->bookingRepo = $this->createMock(BookingRepository::class);
        $this->calculator = new StatisticsCalculator($this->bookingRepo);
    }

    public function testCalculateWithNoBookings(): void
    {
        $lodging = new Lodging();
        $this->bookingRepo->method('findByLodging')->willReturn([]);

        $result = $this->calculator->calculate(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0, $result['revenue']);
        $this->assertSame(0, $result['bookingsCount']);
        $this->assertSame(0, $result['occupiedNights']);
        $this->assertSame(30, $result['totalNights']);
        $this->assertSame(0.0, $result['occupancyRate']);
        $this->assertSame(0, $result['revpar']);
    }

    public function testCalculateWithConfirmedBooking(): void
    {
        $lodging = new Lodging();

        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-05-05'));
        $booking->setCheckout(new \DateTimeImmutable('2026-05-10'));
        $booking->setStatus(BookingStatus::CONFIRMED);
        $booking->setTotalPrice(50000);

        $this->bookingRepo->method('findByLodging')->willReturn([$booking]);

        $result = $this->calculator->calculate(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(50000, $result['revenue']);
        $this->assertSame(1, $result['bookingsCount']);
        $this->assertSame(5, $result['occupiedNights']);
        $this->assertSame(30, $result['totalNights']);
    }

    public function testCalculateExcludesCancelledBookings(): void
    {
        $lodging = new Lodging();

        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-05-05'));
        $booking->setCheckout(new \DateTimeImmutable('2026-05-10'));
        $booking->setStatus(BookingStatus::CANCELLED);
        $booking->setTotalPrice(50000);

        $this->bookingRepo->method('findByLodging')->willReturn([$booking]);

        $result = $this->calculator->calculate(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0, $result['revenue']);
        $this->assertSame(0, $result['bookingsCount']);
    }

    public function testCalculateExcludesPendingBookings(): void
    {
        $lodging = new Lodging();

        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-05-05'));
        $booking->setCheckout(new \DateTimeImmutable('2026-05-10'));
        $booking->setStatus(BookingStatus::PENDING);
        $booking->setTotalPrice(30000);

        $this->bookingRepo->method('findByLodging')->willReturn([$booking]);

        $result = $this->calculator->calculate(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0, $result['revenue']);
        $this->assertSame(0, $result['bookingsCount']);
    }

    public function testCalculateExcludesBookingsOutsidePeriod(): void
    {
        $lodging = new Lodging();

        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-04-01'));
        $booking->setCheckout(new \DateTimeImmutable('2026-04-05'));
        $booking->setStatus(BookingStatus::CONFIRMED);
        $booking->setTotalPrice(40000);

        $this->bookingRepo->method('findByLodging')->willReturn([$booking]);

        $result = $this->calculator->calculate(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0, $result['revenue']);
        $this->assertSame(0, $result['bookingsCount']);
    }

    public function testCalculateClipsOverlapToPeriod(): void
    {
        $lodging = new Lodging();

        // Booking spans 2026-04-28 to 2026-05-05 but period starts 2026-05-01
        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-04-28'));
        $booking->setCheckout(new \DateTimeImmutable('2026-05-05'));
        $booking->setStatus(BookingStatus::CONFIRMED);
        $booking->setTotalPrice(70000);

        $this->bookingRepo->method('findByLodging')->willReturn([$booking]);

        $result = $this->calculator->calculate(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(1, $result['bookingsCount']);
        // Only nights within period: May 1-5 = 4 nights
        $this->assertSame(4, $result['occupiedNights']);
    }

    public function testCalculateWithMultipleLodgings(): void
    {
        $lodging1 = new Lodging();
        $lodging2 = new Lodging();

        $booking1 = new Booking();
        $booking1->setCheckin(new \DateTimeImmutable('2026-05-01'));
        $booking1->setCheckout(new \DateTimeImmutable('2026-05-04'));
        $booking1->setStatus(BookingStatus::CONFIRMED);
        $booking1->setTotalPrice(30000);

        $booking2 = new Booking();
        $booking2->setCheckin(new \DateTimeImmutable('2026-05-10'));
        $booking2->setCheckout(new \DateTimeImmutable('2026-05-15'));
        $booking2->setStatus(BookingStatus::CONFIRMED);
        $booking2->setTotalPrice(50000);

        $this->bookingRepo->method('findByLodging')->willReturnMap([
            [$lodging1, [$booking1]],
            [$lodging2, [$booking2]],
        ]);

        $result = $this->calculator->calculate(
            [$lodging1, $lodging2],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(80000, $result['revenue']);
        $this->assertSame(2, $result['bookingsCount']);
        $this->assertSame(8, $result['occupiedNights']);
        // 30 days * 2 lodgings = 60 total nights
        $this->assertSame(60, $result['totalNights']);
    }

    public function testCalculateOccupancyRateAndRevpar(): void
    {
        $lodging = new Lodging();

        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-05-01'));
        $booking->setCheckout(new \DateTimeImmutable('2026-05-11'));
        $booking->setStatus(BookingStatus::CONFIRMED);
        $booking->setTotalPrice(100000);

        $this->bookingRepo->method('findByLodging')->willReturn([$booking]);

        $result = $this->calculator->calculate(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        // 10 nights / 30 total = 33.33%
        $this->assertSame(33.33, $result['occupancyRate']);
        // 100000 / 30 = 3333 (rounded)
        $this->assertSame(3333, $result['revpar']);
    }

    public function testCalculateWithEmptyLodgings(): void
    {
        $result = $this->calculator->calculate(
            [],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0, $result['revenue']);
        $this->assertSame(0, $result['totalNights']);
        $this->assertSame(0.0, $result['occupancyRate']);
    }
}
