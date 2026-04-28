<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ChangePasswordRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
        assert($data instanceof ChangePasswordRequest);

        $user = $this->security->getUser();
        \assert($user instanceof \App\Entity\User);

        if (!$this->passwordHasher->isPasswordValid($user, $data->currentPassword)) {
            throw new BadRequestHttpException('Current password is incorrect');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->newPassword));
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return null;
    }
}
