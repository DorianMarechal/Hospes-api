<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\AnalyticsDashboard;
use App\Entity\User;
use App\Repository\LodgingRepository;
use App\Service\AdvancedAnalyticsService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LodgingPerformanceProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private AdvancedAnalyticsService $analyticsService,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AnalyticsDashboard
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new AnalyticsDashboard();
        }

        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (null === $lodging) {
            throw new NotFoundHttpException('Lodging not found.');
        }

        $hostProfile = $user->getHostProfile();
        if (null === $hostProfile || !$lodging->getHost()?->getId()?->equals($hostProfile->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging.');
        }

        $request = $this->requestStack->getCurrentRequest();
        $from = $this->parseDate($request?->query->get('from'), '-30 days');
        $to = $this->parseDate($request?->query->get('to'), 'now');

        return $this->analyticsService->lodgingPerformance($lodging, $from, $to);
    }

    private function parseDate(?string $value, string $default): \DateTimeImmutable
    {
        if (null !== $value) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
            if (false !== $date) {
                return $date->setTime(0, 0);
            }
        }

        return (new \DateTimeImmutable($default))->setTime(0, 0);
    }
}
