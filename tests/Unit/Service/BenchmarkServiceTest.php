<?php

namespace App\Tests\Unit\Service;

use App\Dto\AnalyticsDashboard;
use App\Dto\BenchmarkResult;
use App\Entity\Lodging;
use App\Enum\LodgingType;
use App\Service\AdvancedAnalyticsService;
use App\Service\BenchmarkService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class BenchmarkServiceTest extends TestCase
{
    private BenchmarkService $service;
    private EntityManagerInterface $em;
    private Connection $connection;
    private AdvancedAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->em->method('getConnection')->willReturn($this->connection);

        $this->analyticsService = $this->createMock(AdvancedAnalyticsService::class);

        $this->service = new BenchmarkService($this->em, $this->analyticsService);
    }

    /**
     * Creates a Lodging with UUID, city, and type set via reflection.
     */
    private function createLodging(string $city = 'Paris', ?LodgingType $type = LodgingType::GITE): Lodging
    {
        $lodging = new Lodging();

        $idRef = new \ReflectionProperty(Lodging::class, 'id');
        $idRef->setValue($lodging, Uuid::v7());

        $cityRef = new \ReflectionProperty(Lodging::class, 'city');
        $cityRef->setValue($lodging, $city);

        $typeRef = new \ReflectionProperty(Lodging::class, 'type');
        $typeRef->setValue($lodging, $type);

        return $lodging;
    }

    /**
     * Builds a fixed AnalyticsDashboard for own lodging stats.
     */
    private function makeOwnDashboard(int $revpar = 1500, float $occupancyRate = 60.0, int $adr = 2500): AnalyticsDashboard
    {
        return new AnalyticsDashboard(
            revpar: $revpar,
            occupancyRate: $occupancyRate,
            adr: $adr,
        );
    }

    /**
     * Builds a DBAL Result mock that returns the given rows from fetchAllAssociative().
     *
     * @param list<array<string, mixed>> $rows
     */
    private function buildResult(array $rows): Result
    {
        $result = $this->createStub(Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);

        return $result;
    }

    // -------------------------------------------------------------------------
    // No comparables — zero averages, comparablesCount = 0
    // -------------------------------------------------------------------------

    public function testBenchmarkLodgingWithNoComparablesReturnsOwnStatsAndZeroAverages(): void
    {
        $lodging = $this->createLodging('Lyon', LodgingType::CABIN);
        $own = $this->makeOwnDashboard(revpar: 2000, occupancyRate: 75.0, adr: 3000);

        $this->analyticsService
            ->method('lodgingPerformance')
            ->willReturn($own);

        $this->connection
            ->method('executeQuery')
            ->willReturn($this->buildResult([]));

        $from = new \DateTimeImmutable('2026-05-01');
        $to = new \DateTimeImmutable('2026-05-31');

        $result = $this->service->benchmarkLodging($lodging, $from, $to);

        $this->assertInstanceOf(BenchmarkResult::class, $result);

        // Own stats must be forwarded as-is
        $this->assertSame(2000, $result->yourRevpar);
        $this->assertSame(75.0, $result->yourOccupancyRate);
        $this->assertSame(3000, $result->yourAdr);

        // Averages are zero when there are no comparables
        $this->assertSame(0, $result->avgRevpar);
        $this->assertSame(0.0, $result->avgOccupancyRate);
        $this->assertSame(0, $result->avgAdr);
        $this->assertSame(0, $result->comparablesCount);

        // Metadata
        $this->assertSame('Lyon', $result->city);
        $this->assertSame('cabin', $result->lodgingType);
    }

    // -------------------------------------------------------------------------
    // comparablesCount
    // -------------------------------------------------------------------------

    public function testBenchmarkLodgingReturnsCorrectComparablesCount(): void
    {
        $lodging = $this->createLodging('Paris', LodgingType::APARTMENT);
        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());

        $rows = [
            ['revenue' => '30000', 'occupied_nights' => '10', 'booking_count' => '2'],
            ['revenue' => '60000', 'occupied_nights' => '20', 'booking_count' => '3'],
            ['revenue' => '45000', 'occupied_nights' => '15', 'booking_count' => '4'],
        ];

        $this->connection->method('executeQuery')->willReturn($this->buildResult($rows));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(3, $result->comparablesCount);
    }

    // -------------------------------------------------------------------------
    // avgRevpar = round(totalRevenue / (days * count))
    // -------------------------------------------------------------------------

    public function testBenchmarkLodgingCalculatesAvgRevparCorrectly(): void
    {
        $lodging = $this->createLodging('Nice', LodgingType::VILLA);
        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());

        // Period: May 1 → May 31 = 30 days. 2 comparables → 60 totalAvailableNights.
        // Total revenue = 30000 + 60000 = 90000 → avgRevpar = round(90000 / 60) = 1500
        $rows = [
            ['revenue' => '30000', 'occupied_nights' => '10', 'booking_count' => '1'],
            ['revenue' => '60000', 'occupied_nights' => '20', 'booking_count' => '2'],
        ];

        $this->connection->method('executeQuery')->willReturn($this->buildResult($rows));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(1500, $result->avgRevpar);
    }

    // -------------------------------------------------------------------------
    // avgOccupancy = round(totalOccupied / (days * count) * 100, 2)
    // -------------------------------------------------------------------------

    public function testBenchmarkLodgingCalculatesAvgOccupancyCorrectly(): void
    {
        $lodging = $this->createLodging('Marseille', LodgingType::HOUSE);
        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());

        // Period: June 1 → June 30 = 29 days. 1 comparable → 29 totalAvailableNights.
        // Occupied = 15 → occupancy = round(15/29*100, 2) = 51.72
        $rows = [
            ['revenue' => '50000', 'occupied_nights' => '15', 'booking_count' => '2'],
        ];

        $this->connection->method('executeQuery')->willReturn($this->buildResult($rows));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-06-30'),
        );

        $expected = round(15 / 29 * 100, 2);
        $this->assertSame($expected, $result->avgOccupancyRate);
    }

    public function testBenchmarkLodgingAvgOccupancyIsZeroWithNoOccupiedNights(): void
    {
        $lodging = $this->createLodging('Bordeaux', LodgingType::STUDIO);
        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());

        $rows = [
            ['revenue' => '0', 'occupied_nights' => '0', 'booking_count' => '0'],
        ];

        $this->connection->method('executeQuery')->willReturn($this->buildResult($rows));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0.0, $result->avgOccupancyRate);
    }

    // -------------------------------------------------------------------------
    // avgAdr = round(totalRevenue / totalOccupied) when occupied > 0, else 0
    // -------------------------------------------------------------------------

    public function testBenchmarkLodgingCalculatesAvgAdrCorrectly(): void
    {
        $lodging = $this->createLodging('Toulouse', LodgingType::BED_AND_BREAKFAST);
        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());

        // Total revenue = 10000 + 40000 = 50000. Total occupied = 5 + 20 = 25.
        // avgAdr = round(50000 / 25) = 2000
        $rows = [
            ['revenue' => '10000', 'occupied_nights' => '5', 'booking_count' => '1'],
            ['revenue' => '40000', 'occupied_nights' => '20', 'booking_count' => '3'],
        ];

        $this->connection->method('executeQuery')->willReturn($this->buildResult($rows));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(2000, $result->avgAdr);
    }

    public function testBenchmarkLodgingAvgAdrIsZeroWhenTotalOccupiedNightsIsZero(): void
    {
        $lodging = $this->createLodging('Strasbourg', LodgingType::LOFT);
        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());

        // No occupied nights at all → avgAdr must be 0, not a division by zero
        $rows = [
            ['revenue' => '0', 'occupied_nights' => '0', 'booking_count' => '0'],
            ['revenue' => '0', 'occupied_nights' => '0', 'booking_count' => '0'],
        ];

        $this->connection->method('executeQuery')->willReturn($this->buildResult($rows));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(0, $result->avgAdr);
    }

    // -------------------------------------------------------------------------
    // Metadata — city and lodgingType propagated correctly
    // -------------------------------------------------------------------------

    public function testBenchmarkLodgingPropagatesCityAndLodgingType(): void
    {
        $lodging = $this->createLodging('Nantes', LodgingType::HOTEL_ROOM);
        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());

        $this->connection->method('executeQuery')->willReturn($this->buildResult([]));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-01-31'),
        );

        $this->assertSame('Nantes', $result->city);
        $this->assertSame('hotel_room', $result->lodgingType);
    }

    public function testBenchmarkLodgingWithNullTypeUsesEmptyStringForLodgingType(): void
    {
        $lodging = $this->createLodging('Dijon', null);
        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());

        $this->connection->method('executeQuery')->willReturn($this->buildResult([]));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-01-31'),
        );

        $this->assertSame('', $result->lodgingType);
    }

    public function testBenchmarkLodgingWithNullCityUsesEmptyStringForCity(): void
    {
        $lodging = new Lodging();
        $idRef = new \ReflectionProperty(Lodging::class, 'id');
        $idRef->setValue($lodging, Uuid::v7());
        // city left null — getCity() returns null

        $this->analyticsService->method('lodgingPerformance')->willReturn($this->makeOwnDashboard());
        $this->connection->method('executeQuery')->willReturn($this->buildResult([]));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-01-31'),
        );

        $this->assertSame('', $result->city);
    }

    // -------------------------------------------------------------------------
    // Own stats are always forwarded from AdvancedAnalyticsService
    // -------------------------------------------------------------------------

    public function testBenchmarkLodgingForwardsOwnStatsFromAnalyticsService(): void
    {
        $lodging = $this->createLodging('Rennes', LodgingType::BUNGALOW);
        $own = $this->makeOwnDashboard(revpar: 4200, occupancyRate: 88.5, adr: 5600);

        $this->analyticsService
            ->expects($this->once())
            ->method('lodgingPerformance')
            ->with($lodging)
            ->willReturn($own);

        // Provide one comparable so we verify own stats even when comparables exist
        $rows = [
            ['revenue' => '20000', 'occupied_nights' => '8', 'booking_count' => '2'],
        ];
        $this->connection->method('executeQuery')->willReturn($this->buildResult($rows));

        $result = $this->service->benchmarkLodging(
            $lodging,
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-31'),
        );

        $this->assertSame(4200, $result->yourRevpar);
        $this->assertSame(88.5, $result->yourOccupancyRate);
        $this->assertSame(5600, $result->yourAdr);
    }
}
