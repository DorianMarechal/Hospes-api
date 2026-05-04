<?php

namespace App\Service;

use App\Dto\BenchmarkResult;
use App\Entity\Lodging;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;

class BenchmarkService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdvancedAnalyticsService $analyticsService,
    ) {
    }

    public function benchmarkLodging(Lodging $lodging, \DateTimeImmutable $from, \DateTimeImmutable $to): BenchmarkResult
    {
        $city = $lodging->getCity() ?? '';
        $type = $lodging->getType();
        $totalDays = max(1, (int) $from->diff($to)->days);

        // Own performance
        $own = $this->analyticsService->lodgingPerformance($lodging, $from, $to);

        // Find comparable lodgings (same city, same type, excluding own)
        $comparables = $this->em->getConnection()->executeQuery(
            'SELECT l.id,
                    COALESCE(SUM(b.total_price), 0) as revenue,
                    COALESCE(SUM(b.number_of_nights), 0) as occupied_nights,
                    COUNT(DISTINCT b.id) as booking_count
             FROM lodging l
             LEFT JOIN booking b ON b.lodging_id = l.id
                AND b.status IN (?, ?)
                AND b.checkin >= ? AND b.checkout <= ?
             WHERE LOWER(l.city) = LOWER(?)
             AND l.type = ?
             AND l.id != ?
             AND l.is_active = true
             GROUP BY l.id',
            [
                BookingStatus::CONFIRMED->value,
                BookingStatus::COMPLETED->value,
                $from->format('Y-m-d'),
                $to->format('Y-m-d'),
                $city,
                null !== $type ? $type->value : '',
                (string) $lodging->getId(),
            ],
        )->fetchAllAssociative();

        if (empty($comparables)) {
            return new BenchmarkResult(
                yourRevpar: $own->revpar,
                yourOccupancyRate: $own->occupancyRate,
                yourAdr: $own->adr,
                avgRevpar: 0,
                avgOccupancyRate: 0.0,
                avgAdr: 0,
                comparablesCount: 0,
                city: $city,
                lodgingType: null !== $type ? $type->value : '',
            );
        }

        $totalRevenue = 0;
        $totalOccupied = 0;
        $count = \count($comparables);

        foreach ($comparables as $comp) {
            $totalRevenue += (int) $comp['revenue'];
            $totalOccupied += (int) $comp['occupied_nights'];
        }

        $totalAvailableNights = $totalDays * $count;
        $avgRevpar = (int) round($totalRevenue / $totalAvailableNights);
        $avgOccupancy = round($totalOccupied / $totalAvailableNights * 100, 2);
        $avgAdr = $totalOccupied > 0 ? (int) round($totalRevenue / $totalOccupied) : 0;

        return new BenchmarkResult(
            yourRevpar: $own->revpar,
            yourOccupancyRate: $own->occupancyRate,
            yourAdr: $own->adr,
            avgRevpar: $avgRevpar,
            avgOccupancyRate: $avgOccupancy,
            avgAdr: $avgAdr,
            comparablesCount: $count,
            city: $city,
            lodgingType: null !== $type ? $type->value : '',
        );
    }
}
