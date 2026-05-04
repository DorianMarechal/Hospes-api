<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\UnreadCountsResult;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;

class UnreadCountsProvider implements ProviderInterface
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private MessageRepository $messageRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UnreadCountsResult
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        $result = new UnreadCountsResult();
        $result->unreadNotifications = $this->notificationRepository->countUnread($user);
        $result->unreadMessages = $this->messageRepository->countUnreadForUser($user);

        return $result;
    }
}
