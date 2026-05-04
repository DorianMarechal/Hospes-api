<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\TaskRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class MyTasksProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private TaskRepository $taskRepository,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return \App\Entity\Task[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $request = $this->requestStack->getCurrentRequest();
        $from = $request?->query->get('from');
        $to = $request?->query->get('to');

        $hostProfile = $user->getHostProfile();
        if (null !== $hostProfile) {
            return $this->taskRepository->findForHost($hostProfile, $from, $to);
        }

        return $this->taskRepository->findForUser($user, $from, $to);
    }
}
