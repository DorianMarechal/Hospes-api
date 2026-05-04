<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ChangePasswordRequest;
use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$data instanceof ChangePasswordRequest) {
            throw new \InvalidArgumentException('Expected '.ChangePasswordRequest::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new HttpException(401, 'Authentication required');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data->currentPassword)) {
            throw new BadRequestHttpException('Current password is incorrect');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->newPassword));
        $user->setUpdatedAt(new \DateTimeImmutable());

        // Invalidate all refresh tokens on password change
        $this->em->createQuery('DELETE FROM '.RefreshToken::class.' rt WHERE rt.username = :username')
            ->setParameter('username', $user->getUserIdentifier())
            ->execute();

        $this->em->flush();

        return null;
    }
}
