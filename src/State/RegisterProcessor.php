<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterProcessor implements ProcessorInterface{

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    )
    {}

    public function process(mixed $data, Operation $operation, array $uriVariable = [], array $context = []): mixed
    {

        if($this->userRepository->findOneBy(['email' => $data->email])){
            throw new HttpException(422, 'This email is already registered');
        }

        $user = new User();

        $user->setEmail($data->email);
        $user->setLastName($data->lastName);
        $user->setFirstName($data->firstName);

        if($data->phone !== null){
            $user->setPhone($data->phone);
        }

        if($data->accountType === 'host'){
            $user->setRoles(['ROLE_HOST']);
        } else {
            $user->setRoles(['ROLE_CUSTOMER']);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setUpdatedAt(new DateTimeImmutable());
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

}