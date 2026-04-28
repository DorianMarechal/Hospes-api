<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationReadAllProcessor implements ProcessorInterface
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->notificationRepository->markAllRead($user);

        return null;
    }
}
