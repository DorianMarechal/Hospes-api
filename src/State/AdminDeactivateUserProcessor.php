<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminDeactivateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private Security $security,
        private AuditLogger $auditLogger,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $user = $this->userRepository->find($uriVariables['id']);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        /** @var User $admin */
        $admin = $this->security->getUser();
        if ($user->getId()?->equals($admin->getId())) {
            throw new HttpException(422, 'You cannot deactivate your own account');
        }

        $user->setIsActive(false);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->auditLogger->log('deactivate_user', 'User', $user->getId(), ['email' => $user->getEmail()]);
        $this->em->flush();

        return $user;
    }
}
