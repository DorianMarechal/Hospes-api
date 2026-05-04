<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\PropertyOwnerRepository;
use App\Service\OwnerRevenueCalculator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OwnerStatementsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private PropertyOwnerRepository $ownerRepository,
        private OwnerRevenueCalculator $revenueCalculator,
    ) {
    }

    /**
     * @return \App\Dto\OwnerStatement[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $owner = $this->ownerRepository->findByUser($user);
        if (null === $owner) {
            throw new AccessDeniedHttpException('You are not a property owner.');
        }

        return $this->revenueCalculator->calculateStatements($owner);
    }
}
