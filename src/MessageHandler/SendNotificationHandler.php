<?php

namespace App\MessageHandler;

use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Message\SendNotificationMessage;
use App\Repository\UserRepository;
use App\Service\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class SendNotificationHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MercurePublisher $mercurePublisher,
    ) {
    }

    public function __invoke(SendNotificationMessage $message): void
    {
        $user = $this->userRepository->find(Uuid::fromString($message->userId));
        if (null === $user) {
            return;
        }

        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType(NotificationType::from($message->type));
        $notification->setTitle($message->title);
        $notification->setContent($message->content);
        $notification->setRelatedEntityType($message->relatedEntityType);
        $notification->setRelatedEntityId(null !== $message->relatedEntityId ? Uuid::fromString($message->relatedEntityId) : null);
        $notification->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->mercurePublisher->publishNotification($notification);
    }
}
