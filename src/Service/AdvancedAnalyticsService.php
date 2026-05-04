<?php

namespace App\Service;

use App\Dto\AnalyticsDashboard;
use App\Entity\Lodging;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;

class AdvancedAnalyticsService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @param Lodging[] $lodgings
     */
    public function dashboard(array $lodgings, \DateTimeImmutable $from, \DateTimeImmutable $to): AnalyticsDashboard
    {
        if (empty($lodgings)) {
            return new AnalyticsDashboard();
        }

        $lodgingIds = array_filter(array_map(fn (Lodging $l) => $l->getId()?->toRfc4122(), $lodgings));
        if (empty($lodgingIds)) {
            return new AnalyticsDashboard();
        }

        $totalDays = max(1, (int) $from->diff($to)->days);
        $totalNights = $totalDays * \count($lodgings);
        $currency = $lodgings[0]->getCurrency();

        // Current period stats
        $current = $this->periodStats($lodgingIds, $from, $to);

        // Last year same period
        $lastYearFrom = $from->modify('-1 year');
        $lastYearTo = $to->modify('-1 year');
        $lastYear = $this->periodStats($lodgingIds, $lastYearFrom, $lastYearTo);

        // Future bookings (from now)
        $future = $this->futureStats($lodgingIds);

        $occupancyRate = round($current['occupiedNights'] / $totalNights * 100, 2);
        $revpar = (int) round($current['revenue'] / $totalNights);
        $adr = $current['occupiedNights'] > 0 ? (int) round($current['revenue'] / $current['occupiedNights']) : 0;
        $avgStay = $current['bookingCount'] > 0 ? round($current['occupiedNights'] / $current['bookingCount'], 1) : 0.0;
        $revenueGrowth = $lastYear['revenue'] > 0
            ? round(($current['revenue'] - $lastYear['revenue']) / $lastYear['revenue'] * 100, 1)
            : 0.0;

        return new AnalyticsDashboard(
            revpar: $revpar,
            occupancyRate: $occupancyRate,
            adr: $adr,
            averageStayDuration: $avgStay,
            totalRevenue: $current['revenue'],
            bookingCount: $current['bookingCount'],
            totalRevenueLastYear: $lastYear['revenue'],
            bookingCountLastYear: $lastYear['bookingCount'],
            revenueGrowth: $revenueGrowth,
            futureRevenue: $future['revenue'],
            futureBookings: $future['bookingCount'],
            currency: $currency,
            periodFrom: $from->format('Y-m-d'),
            periodTo: $to->format('Y-m-d'),
        );
    }

    public function lodgingPerformance(Lodging $lodging, \DateTimeImmutable $from, \DateTimeImmutable $to): AnalyticsDashboard
    {
        return $this->dashboard([$lodging], $from, $to);
    }

    /**
     * @param string[] $lodgingIds
     *
     * @return array{revenue: int, bookingCount: int, occupiedNights: int}
     */
    private function periodStats(array $lodgingIds, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $result = $this->em->getConnection()->executeQuery(
            'SELECT COALESCE(SUM(b.total_price), 0) as revenue,
                    COUNT(*) as booking_count,
                    COALESCE(SUM(b.number_of_nights), 0) as occupied_nights
             FROM booking b
             WHERE b.lodging_id IN (?)
             AND b.status IN (?, ?)
             AND b.checkin >= ?
             AND b.checkout <= ?',
            [
                $lodgingIds,
                BookingStatus::CONFIRMED->value,
                BookingStatus::COMPLETED->value,
                $from->format('Y-m-d'),
                $to->format('Y-m-d'),
            ],
            [
                \Doctrine\DBAL\ArrayParameterType::STRING,
                \Doctrine\DBAL\ParameterType::STRING,
                \Doctrine\DBAL\ParameterType::STRING,
                \Doctrine\DBAL\ParameterType::STRING,
                \Doctrine\DBAL\ParameterType::STRING,
            ],
        )->fetchAssociative();

        return [
            'revenue' => (int) ($result['revenue'] ?? 0),
            'bookingCount' => (int) ($result['booking_count'] ?? 0),
            'occupiedNights' => (int) ($result['occupied_nights'] ?? 0),
        ];
    }

    /**
     * @param string[] $lodgingIds
     *
     * @return array{revenue: int, bookingCount: int}
     */
    private function futureStats(array $lodgingIds): array
    {
        $result = $this->em->getConnection()->executeQuery(
            'SELECT COALESCE(SUM(b.total_price), 0) as revenue,
                    COUNT(*) as booking_count
             FROM booking b
             WHERE b.lodging_id IN (?)
             AND b.status = ?
             AND b.checkin > CURRENT_DATE',
            [
                $lodgingIds,
                BookingStatus::CONFIRMED->value,
            ],
            [
                \Doctrine\DBAL\ArrayParameterType::STRING,
                \Doctrine\DBAL\ParameterType::STRING,
            ],
        )->fetchAssociative();

        return [
            'revenue' => (int) ($result['revenue'] ?? 0),
            'bookingCount' => (int) ($result['booking_count'] ?? 0),
        ];
    }
}
