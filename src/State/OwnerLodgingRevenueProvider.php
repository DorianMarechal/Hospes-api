<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\OwnerLodgingRevenue;
use App\Entity\User;
use App\Repository\LodgingRepository;
use App\Repository\PropertyOwnerRepository;
use App\Service\OwnerRevenueCalculator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OwnerLodgingRevenueProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private PropertyOwnerRepository $ownerRepository,
        private LodgingRepository $lodgingRepository,
        private OwnerRevenueCalculator $revenueCalculator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): OwnerLodgingRevenue
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $owner = $this->ownerRepository->findByUser($user);
        if (null === $owner) {
            throw new AccessDeniedHttpException('You are not a property owner.');
        }

        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (null === $lodging) {
            throw new NotFoundHttpException('Lodging not found.');
        }

        if (null === $lodging->getPropertyOwner() || !$lodging->getPropertyOwner()->getId()?->equals($owner->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging.');
        }

        return $this->revenueCalculator->calculateForLodging($lodging, $owner);
    }
}
