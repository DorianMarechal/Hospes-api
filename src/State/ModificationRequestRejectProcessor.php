<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BookingModificationRequest;
use App\Enum\ModificationRequestStatus;
use App\Service\NotificationDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ModificationRequestRejectProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationDispatcher $notificationDispatcher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof BookingModificationRequest) {
            throw new \InvalidArgumentException('Expected '.BookingModificationRequest::class);
        }

        if (ModificationRequestStatus::PENDING !== $data->getStatus()) {
            throw new HttpException(422, 'Only pending modification requests can be rejected');
        }

        if ($data->getExpiresAt() <= new \DateTimeImmutable()) {
            $data->setStatus(ModificationRequestStatus::EXPIRED);
            $this->notificationDispatcher->modificationExpired($data);
            $this->entityManager->flush();
            $this->notificationDispatcher->publishPendingNotifications();

            throw new HttpException(422, 'This modification request has expired');
        }

        $now = new \DateTimeImmutable();
        $data->setStatus(ModificationRequestStatus::REJECTED);
        $data->setRespondedAt($now);

        $this->notificationDispatcher->modificationRejected($data);
        $this->entityManager->flush();
        $this->notificationDispatcher->publishPendingNotifications();

        return $data;
    }
}
