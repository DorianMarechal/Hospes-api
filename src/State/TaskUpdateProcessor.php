<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TaskUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Task) {
            throw new \InvalidArgumentException('Expected '.Task::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        // Verify access: must be assignee or host
        $hostProfile = $user->getHostProfile();
        $isHost = null !== $hostProfile && $data->getHostProfile()?->getId()?->equals($hostProfile->getId());
        $isAssignee = $data->getAssignee()?->getId()?->equals($user->getId());

        if (!$isHost && !$isAssignee) {
            throw new AccessDeniedHttpException('You do not have access to this task.');
        }

        if (TaskStatus::COMPLETED === $data->getStatus() && null === $data->getCompletedAt()) {
            $data->setCompletedAt(new \DateTimeImmutable());
        }

        $data->setUpdatedAt(new \DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
