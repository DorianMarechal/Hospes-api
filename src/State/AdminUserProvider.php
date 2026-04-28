<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminUserProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $user = $this->userRepository->find($uriVariables['id']);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        return $user;
    }
}
