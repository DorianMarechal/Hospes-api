<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\LodgingRepository;
use App\Service\NotificationDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private EntityManagerInterface $em,
        private NotificationDispatcher $notificationDispatcher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Task
    {
        if (!$data instanceof Task) {
            throw new \InvalidArgumentException('Expected '.Task::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        $hostProfile = $user->getHostProfile();
        if (null === $hostProfile) {
            throw new AccessDeniedHttpException('Host profile required.');
        }

        $lodging = $data->getLodging();
        if (null === $lodging) {
            throw new NotFoundHttpException('Lodging is required.');
        }

        // Refresh lodging from DB to ensure it exists and we have the correct host
        $lodging = $this->lodgingRepository->find($lodging->getId());
        if (null === $lodging || !$lodging->getHost()?->getId()?->equals($hostProfile->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging.');
        }

        $now = new \DateTimeImmutable();
        $data->setLodging($lodging);
        $data->setHostProfile($hostProfile);
        $data->setCreatedAt($now);
        $data->setUpdatedAt($now);

        $this->em->persist($data);
        $this->em->flush();

        // Notify assignee if set
        $assignee = $data->getAssignee();
        if (null !== $assignee && !$assignee->getId()?->equals($user->getId())) {
            $this->notificationDispatcher->taskAssigned(
                $assignee,
                $data->getType()->value,
                $lodging->getName() ?? '',
                $data->getId(),
            );
            $this->em->flush();
            $this->notificationDispatcher->publishPendingNotifications();
        }

        return $data;
    }
}
