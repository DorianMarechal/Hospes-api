<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminReactivateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $user = $this->userRepository->find($uriVariables['id']);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $user->setIsActive(true);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $user;
    }
}
