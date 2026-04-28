<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\UserRepository;

class AdminUsersProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @return \App\Entity\User[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->userRepository->findAll();
    }
}
