<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariable = [], array $context = []): mixed
    {
        $user = new User();

        $user->setEmail($data->email);
        $user->setLastName($data->lastName);
        $user->setFirstName($data->firstName);

        if (null !== $data->phone) {
            $user->setPhone($data->phone);
        }

        if ('host' === $data->accountType) {
            $user->setRoles(['ROLE_HOST']);
        } else {
            $user->setRoles(['ROLE_CUSTOMER']);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());
        $user->setIsActive(true);

        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new HttpException(409, 'This email is already registered');
        }

        return $user;
    }
}
