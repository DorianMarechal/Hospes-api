<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\LodgingRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MyLodgingsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
    ) {
    }

    /**
     * @return \App\Entity\Lodging[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $hostProfile = $user->getHostProfile();
        if (!$hostProfile) {
            return [];
        }

        return $this->lodgingRepository->findBy(['host' => $hostProfile]);
    }
}
