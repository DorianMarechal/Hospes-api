<?php

namespace App\Tests\Unit\Service;

use App\Dto\AnalyticsDashboard;
use App\Entity\Lodging;
use App\Service\AdvancedAnalyticsService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class AdvancedAnalyticsServiceTest extends TestCase
{
    private AdvancedAnalyticsService $service;
    private EntityManagerInterface $em;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->em->method('getConnection')->willReturn($this->connection);
        $this->service = new AdvancedAnalyticsService($this->em);
    }

    /**
     * Creates a Lodging with a UUID id and a currency set.
     */
    private function createLodging(string $currency = 'EUR'): Lodging
    {
        $lodging = new Lodging();

        $idRef = new \ReflectionProperty(Lodging::class, 'id');
        $idRef->setValue($lodging, Uuid::v7());

        $currencyRef = new \ReflectionProperty(Lodging::class, 'currency');
        $currencyRef->setValue($lodging, $currency);

        return $lodging;
    }

    /**
     * Builds a DBAL Result mock returning a single associative row.
     *
     * @param array<string, mixed> $row
     */
    private function buildResult(array $row): Result
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn($row);

        return $result;
    }

    /**
     * Configures the connection mock to return results in order:
     * 1st call = current period, 2nd call = last year, 3rd call = future.
     */
    private function configureConnection(array $current, array $lastYear, array $future): void
    {
        $this->connection->expects($this->exactly(3))
            ->method('executeQuery')
            ->willReturnOnConsecutiveCalls(
                $this->buildResult($current),
                $this->buildResult($lastYear),
                $this->buildResult($future),
            );
    }

    // -------------------------------------------------------------------------
    // dashboard — empty lodgings
    // -------------------------------------------------------------------------

    public function testDashboardWithEmptyLodgingsReturnsEmptyDashboard(): void
    {
        $this->connection->expects($this->never())->method('executeQuery');

        $result = $this->service->dashboard(
            [],
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-01-31'),
        );

        $this->assertInstanceOf(AnalyticsDashboard::class, $result);
        $this->assertSame(0, $result->revpar);
        $this->assertSame(0, $result->totalRevenue);
        $this->assertSame(0, $result->bookingCount);
        $this->assertSame(0.0, $result->occupancyRate);
    }

    public function testDashboardWithLodgingsWithoutIdsReturnsEmptyDashboard(): void
    {
        // Lodging has no id assigned — getId() returns null
        $lodging = new Lodging();

        $this->connection->expects($this->never())->method('executeQuery');

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-01-31'),
        );

        $this->assertSame(0, $result->totalRevenue);
    }

    // -------------------------------------------------------------------------
    // RevPAR = revenue / totalNights
    // -------------------------------------------------------------------------

    public function testDashboardCalculatesRevparCorrectly(): void
    {
        $lodging = $this->createLodging();

        // Period: May 1 → May 31 = 30 days, 1 lodging → 30 totalNights
        // Revenue = 60000 → RevPAR = 60000 / 30 = 2000
        $this->configureConnection(
            ['revenue' => '60000', 'booking_count' => '2', 'occupied_nights' => '10'],
            ['revenue' => '0',     'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0',     'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(2000, $result->revpar);
    }

    // -------------------------------------------------------------------------
    // ADR = revenue / occupiedNights
    // -------------------------------------------------------------------------

    public function testDashboardCalculatesAdrCorrectly(): void
    {
        $lodging = $this->createLodging();

        // Revenue = 45000, occupiedNights = 9 → ADR = 5000
        $this->configureConnection(
            ['revenue' => '45000', 'booking_count' => '3', 'occupied_nights' => '9'],
            ['revenue' => '0',     'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0',     'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(5000, $result->adr);
    }

    public function testDashboardAdrIsZeroWhenNoOccupiedNights(): void
    {
        $lodging = $this->createLodging();

        $this->configureConnection(
            ['revenue' => '0', 'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0', 'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0', 'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0, $result->adr);
    }

    // -------------------------------------------------------------------------
    // Occupancy rate = occupiedNights / totalNights * 100
    // -------------------------------------------------------------------------

    public function testDashboardCalculatesOccupancyRateCorrectly(): void
    {
        $lodging = $this->createLodging();

        // Period: 30 days, 1 lodging → 30 totalNights. Occupied = 15 → 50%
        $this->configureConnection(
            ['revenue' => '30000', 'booking_count' => '2', 'occupied_nights' => '15'],
            ['revenue' => '0',     'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0',     'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(50.0, $result->occupancyRate);
    }

    public function testDashboardOccupancyRateWithMultipleLodgings(): void
    {
        $lodging1 = $this->createLodging();
        $lodging2 = $this->createLodging();

        // 2 lodgings × 30 days = 60 totalNights. Occupied = 12 → 20%
        $this->configureConnection(
            ['revenue' => '24000', 'booking_count' => '4', 'occupied_nights' => '12'],
            ['revenue' => '0',     'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0',     'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging1, $lodging2],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(20.0, $result->occupancyRate);
    }

    // -------------------------------------------------------------------------
    // Revenue growth = (current - lastYear) / lastYear * 100
    // -------------------------------------------------------------------------

    public function testDashboardCalculatesRevenueGrowthCorrectly(): void
    {
        $lodging = $this->createLodging();

        // Current = 110000, last year = 100000 → growth = 10%
        $this->configureConnection(
            ['revenue' => '110000', 'booking_count' => '5', 'occupied_nights' => '20'],
            ['revenue' => '100000', 'booking_count' => '4', 'occupied_nights' => '18'],
            ['revenue' => '0',      'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(10.0, $result->revenueGrowth);
    }

    public function testDashboardRevenueGrowthIsZeroWhenNoLastYearRevenue(): void
    {
        $lodging = $this->createLodging();

        $this->configureConnection(
            ['revenue' => '50000', 'booking_count' => '3', 'occupied_nights' => '10'],
            ['revenue' => '0',     'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0',     'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0.0, $result->revenueGrowth);
    }

    public function testDashboardNegativeRevenueGrowth(): void
    {
        $lodging = $this->createLodging();

        // Current = 80000, last year = 100000 → growth = -20%
        $this->configureConnection(
            ['revenue' => '80000',  'booking_count' => '3', 'occupied_nights' => '15'],
            ['revenue' => '100000', 'booking_count' => '5', 'occupied_nights' => '20'],
            ['revenue' => '0',      'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(-20.0, $result->revenueGrowth);
    }

    // -------------------------------------------------------------------------
    // Average stay duration = occupiedNights / bookingCount
    // -------------------------------------------------------------------------

    public function testDashboardCalculatesAverageStayDuration(): void
    {
        $lodging = $this->createLodging();

        // 15 occupied nights / 3 bookings = 5 nights average
        $this->configureConnection(
            ['revenue' => '30000', 'booking_count' => '3', 'occupied_nights' => '15'],
            ['revenue' => '0',     'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0',     'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(5.0, $result->averageStayDuration);
    }

    public function testDashboardAverageStayIsZeroWhenNoBookings(): void
    {
        $lodging = $this->createLodging();

        $this->configureConnection(
            ['revenue' => '0', 'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0', 'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0', 'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0.0, $result->averageStayDuration);
    }

    // -------------------------------------------------------------------------
    // Future bookings
    // -------------------------------------------------------------------------

    public function testDashboardIncludesFutureRevenueAndBookings(): void
    {
        $lodging = $this->createLodging();

        $this->configureConnection(
            ['revenue' => '20000', 'booking_count' => '2', 'occupied_nights' => '6'],
            ['revenue' => '0',     'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '75000', 'booking_count' => '5'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(75000, $result->futureRevenue);
        $this->assertSame(5, $result->futureBookings);
    }

    // -------------------------------------------------------------------------
    // Period and currency metadata
    // -------------------------------------------------------------------------

    public function testDashboardSetsPeriodDatesAndCurrency(): void
    {
        $lodging = $this->createLodging('USD');

        $this->configureConnection(
            ['revenue' => '0', 'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0', 'booking_count' => '0', 'occupied_nights' => '0'],
            ['revenue' => '0', 'booking_count' => '0'],
        );

        $result = $this->service->dashboard(
            [$lodging],
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-06-30'),
        );

        $this->assertSame('2026-06-01', $result->periodFrom);
        $this->assertSame('2026-06-30', $result->periodTo);
        $this->assertSame('USD', $result->currency);
    }

    // -------------------------------------------------------------------------
    // lodgingPerformance delegates to dashboard
    // -------------------------------------------------------------------------

    public function testLodgingPerformanceDelegatesToDashboard(): void
    {
        $lodging = $this->createLodging();

        // dashboard called with [$lodging] — 3 SQL queries expected
        $this->connection->expects($this->exactly(3))
            ->method('executeQuery')
            ->willReturnOnConsecutiveCalls(
                $this->buildResult(['revenue' => '40000', 'booking_count' => '4', 'occupied_nights' => '12']),
                $this->buildResult(['revenue' => '30000', 'booking_count' => '3', 'occupied_nights' => '10']),
                $this->buildResult(['revenue' => '10000', 'booking_count' => '1']),
            );

        $from = new \DateTimeImmutable('2026-05-01');
        $to = new \DateTimeImmutable('2026-05-31');

        $result = $this->service->lodgingPerformance($lodging, $from, $to);

        $this->assertInstanceOf(AnalyticsDashboard::class, $result);
        $this->assertSame(40000, $result->totalRevenue);
        $this->assertSame(4, $result->bookingCount);
        $this->assertSame('2026-05-01', $result->periodFrom);
        $this->assertSame('2026-05-31', $result->periodTo);
    }

    public function testLodgingPerformanceRevparCalculation(): void
    {
        $lodging = $this->createLodging();

        // 30-day period, 1 lodging → 30 totalNights
        // Revenue = 90000 → RevPAR = 90000 / 30 = 3000
        $this->connection->expects($this->exactly(3))
            ->method('executeQuery')
            ->willReturnOnConsecutiveCalls(
                $this->buildResult(['revenue' => '90000', 'booking_count' => '3', 'occupied_nights' => '15']),
                $this->buildResult(['revenue' => '0',     'booking_count' => '0', 'occupied_nights' => '0']),
                $this->buildResult(['revenue' => '0',     'booking_count' => '0']),
            );

        $result = $this->service->lodgingPerformance(
            $lodging,
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(3000, $result->revpar);
    }
}
