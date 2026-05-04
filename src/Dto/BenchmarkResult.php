<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use App\Entity\Lodging;
use App\State\LodgingBenchmarkProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Benchmark',
    operations: [
        new Get(
            uriTemplate: '/me/analytics/lodgings/{lodgingId}/benchmark',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class)],
            security: "is_granted('ROLE_HOST')",
            provider: LodgingBenchmarkProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['benchmark:read']],
)]
class BenchmarkResult
{
    public function __construct(
        #[Groups(['benchmark:read'])]
        public readonly int $yourRevpar = 0,
        #[Groups(['benchmark:read'])]
        public readonly float $yourOccupancyRate = 0.0,
        #[Groups(['benchmark:read'])]
        public readonly int $yourAdr = 0,
        #[Groups(['benchmark:read'])]
        public readonly int $avgRevpar = 0,
        #[Groups(['benchmark:read'])]
        public readonly float $avgOccupancyRate = 0.0,
        #[Groups(['benchmark:read'])]
        public readonly int $avgAdr = 0,
        #[Groups(['benchmark:read'])]
        public readonly int $comparablesCount = 0,
        #[Groups(['benchmark:read'])]
        public readonly string $city = '',
        #[Groups(['benchmark:read'])]
        public readonly string $lodgingType = '',
    ) {
    }
}
