<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MyFavoritesProvider implements ProviderInterface
{
    public function __construct(
        private FavoriteRepository $favoriteRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $this->favoriteRepository->findByUser($user);
    }
}
