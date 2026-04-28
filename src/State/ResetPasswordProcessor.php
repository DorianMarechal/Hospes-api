<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ResetPasswordRequest;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        assert($data instanceof ResetPasswordRequest);

        $user = $this->userRepository->findOneBy(['resetToken' => $data->token]);

        if (!$user) {
            throw new BadRequestHttpException('Invalid or expired reset token');
        }

        if ($user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new BadRequestHttpException('Invalid or expired reset token');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->newPassword));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return null;
    }
}
