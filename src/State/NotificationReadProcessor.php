<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class NotificationReadProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Notification
    {
        if (!$data instanceof Notification) {
            throw new \InvalidArgumentException('Expected '.Notification::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        if (true !== $data->getUser()?->getId()?->equals($user->getId())) {
            throw new AccessDeniedHttpException('You do not own this notification');
        }

        if (!$data->isRead()) {
            throw new BadRequestHttpException('Only marking as read is allowed');
        }

        $data->setReadAt(new \DateTimeImmutable());

        $this->em->flush();

        return $data;
    }
}
