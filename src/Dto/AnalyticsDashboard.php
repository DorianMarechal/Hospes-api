<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use App\Entity\Lodging;
use App\State\AnalyticsDashboardProvider;
use App\State\LodgingPerformanceProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Analytics',
    operations: [
        new Get(
            uriTemplate: '/me/analytics/dashboard',
            security: "is_granted('ROLE_HOST')",
            provider: AnalyticsDashboardProvider::class,
        ),
        new Get(
            uriTemplate: '/me/analytics/lodgings/{lodgingId}/performance',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class)],
            security: "is_granted('ROLE_HOST')",
            provider: LodgingPerformanceProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['analytics:read']],
)]
class AnalyticsDashboard
{
    public function __construct(
        #[Groups(['analytics:read'])]
        public readonly int $revpar = 0,
        #[Groups(['analytics:read'])]
        public readonly float $occupancyRate = 0.0,
        #[Groups(['analytics:read'])]
        public readonly int $adr = 0,
        #[Groups(['analytics:read'])]
        public readonly float $averageStayDuration = 0.0,
        #[Groups(['analytics:read'])]
        public readonly int $totalRevenue = 0,
        #[Groups(['analytics:read'])]
        public readonly int $bookingCount = 0,
        #[Groups(['analytics:read'])]
        public readonly int $totalRevenueLastYear = 0,
        #[Groups(['analytics:read'])]
        public readonly int $bookingCountLastYear = 0,
        #[Groups(['analytics:read'])]
        public readonly float $revenueGrowth = 0.0,
        #[Groups(['analytics:read'])]
        public readonly int $futureRevenue = 0,
        #[Groups(['analytics:read'])]
        public readonly int $futureBookings = 0,
        #[Groups(['analytics:read'])]
        public readonly string $currency = 'EUR',
        #[Groups(['analytics:read'])]
        public readonly string $periodFrom = '',
        #[Groups(['analytics:read'])]
        public readonly string $periodTo = '',
    ) {
    }
}
