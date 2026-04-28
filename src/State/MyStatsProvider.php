<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\StatsResult;
use App\Entity\User;
use App\Repository\LodgingRepository;
use App\Service\StatisticsCalculator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MyStatsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private StatisticsCalculator $statisticsCalculator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StatsResult
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $hostProfile = $user->getHostProfile();
        if (!$hostProfile) {
            return new StatsResult();
        }

        $lodgings = $this->lodgingRepository->findBy(['host' => $hostProfile]);

        $filters = $context['filters'] ?? [];
        [$from, $to] = $this->parsePeriod($filters);

        $stats = $this->statisticsCalculator->calculate($lodgings, $from, $to);

        return new StatsResult(...$stats);
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function parsePeriod(array $filters): array
    {
        if (!empty($filters['from']) && !empty($filters['to'])) {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d', $filters['from']);
            $to = \DateTimeImmutable::createFromFormat('Y-m-d', $filters['to']);
            if (!$from || !$to) {
                throw new BadRequestHttpException('Invalid date format. Use YYYY-MM-DD');
            }

            return [$from->setTime(0, 0), $to->setTime(0, 0)];
        }

        // Default: current month
        $now = new \DateTimeImmutable();
        $from = $now->modify('first day of this month')->setTime(0, 0);
        $to = $now->modify('first day of next month')->setTime(0, 0);

        return [$from, $to];
    }
}
