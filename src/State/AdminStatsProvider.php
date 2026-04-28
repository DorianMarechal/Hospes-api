<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\StatsResult;
use App\Repository\LodgingRepository;
use App\Service\StatisticsCalculator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminStatsProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private StatisticsCalculator $statisticsCalculator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StatsResult
    {
        $filters = $context['filters'] ?? [];

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d', $filters['from']);
            $to = \DateTimeImmutable::createFromFormat('Y-m-d', $filters['to']);
            if (!$from || !$to) {
                throw new BadRequestHttpException('Invalid date format. Use YYYY-MM-DD');
            }
            $from = $from->setTime(0, 0);
            $to = $to->setTime(0, 0);
        } else {
            $now = new \DateTimeImmutable();
            $from = $now->modify('first day of this month')->setTime(0, 0);
            $to = $now->modify('first day of next month')->setTime(0, 0);
        }

        $lodgings = $this->lodgingRepository->findAll();
        $stats = $this->statisticsCalculator->calculate($lodgings, $from, $to);

        return new StatsResult(...$stats);
    }
}
