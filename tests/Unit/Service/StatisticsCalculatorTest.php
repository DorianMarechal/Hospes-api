<?php

namespace App\Tests\Unit\Service;

use App\Entity\Lodging;
use App\Repository\BookingRepository;
use App\Service\StatisticsCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class StatisticsCalculatorTest extends TestCase
{
    private StatisticsCalculator $calculator;
    private BookingRepository $bookingRepo;

    protected function setUp(): void
    {
        $this->bookingRepo = $this->createMock(BookingRepository::class);
        $this->calculator = new StatisticsCalculator($this->bookingRepo);
    }

    private function createLodgingWithId(): Lodging
    {
        $lodging = new Lodging();
        $ref = new \ReflectionProperty(Lodging::class, 'id');
        $ref->setValue($lodging, Uuid::v7());

        return $lodging;
    }

    public function testCalculateWithNoBookings(): void
    {
        $lodging = $this->createLodgingWithId();

        $this->bookingRepo->method('aggregateStats')->willReturn([
            'revenue' => 0,
            'bookingsCount' => 0,
            'occupiedNights' => 0,
        ]);

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
        $lodging = $this->createLodgingWithId();

        $this->bookingRepo->method('aggregateStats')->willReturn([
            'revenue' => 50000,
            'bookingsCount' => 1,
            'occupiedNights' => 5,
        ]);

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
        $lodging = $this->createLodgingWithId();

        // aggregateStats already excludes cancelled/pending in SQL
        $this->bookingRepo->method('aggregateStats')->willReturn([
            'revenue' => 0,
            'bookingsCount' => 0,
            'occupiedNights' => 0,
        ]);

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
        $lodging = $this->createLodgingWithId();

        $this->bookingRepo->method('aggregateStats')->willReturn([
            'revenue' => 0,
            'bookingsCount' => 0,
            'occupiedNights' => 0,
        ]);

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
        $lodging = $this->createLodgingWithId();

        // aggregateStats already filters by date range in SQL
        $this->bookingRepo->method('aggregateStats')->willReturn([
            'revenue' => 0,
            'bookingsCount' => 0,
            'occupiedNights' => 0,
        ]);

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
        $lodging = $this->createLodgingWithId();

        // Booking 04-28 to 05-05 clipped to May 1-5 = 4 nights (done in SQL)
        $this->bookingRepo->method('aggregateStats')->willReturn([
            'revenue' => 70000,
            'bookingsCount' => 1,
            'occupiedNights' => 4,
        ]);

        $result = $this->calculator->calculate(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(1, $result['bookingsCount']);
        $this->assertSame(4, $result['occupiedNights']);
    }

    public function testCalculateWithMultipleLodgings(): void
    {
        $lodging1 = $this->createLodgingWithId();
        $lodging2 = $this->createLodgingWithId();

        $this->bookingRepo->method('aggregateStats')->willReturn([
            'revenue' => 80000,
            'bookingsCount' => 2,
            'occupiedNights' => 8,
        ]);

        $result = $this->calculator->calculate(
            [$lodging1, $lodging2],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(80000, $result['revenue']);
        $this->assertSame(2, $result['bookingsCount']);
        $this->assertSame(8, $result['occupiedNights']);
        $this->assertSame(60, $result['totalNights']);
    }

    public function testCalculateOccupancyRateAndRevpar(): void
    {
        $lodging = $this->createLodgingWithId();

        $this->bookingRepo->method('aggregateStats')->willReturn([
            'revenue' => 100000,
            'bookingsCount' => 1,
            'occupiedNights' => 10,
        ]);

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
