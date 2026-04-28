<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\LodgingRepository;
use App\Repository\PriceOverrideRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PriceOverrideCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private PriceOverrideRepository $priceOverrideRepository,
    ) {
    }

    /**
     * @return \App\Entity\PriceOverride[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $user = $this->security->getUser();
        \assert($user instanceof User);

        $hostProfile = $user->getHostProfile();
        if (!$hostProfile || !$lodging->getHost()?->getId()?->equals($hostProfile->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging');
        }

        return $this->priceOverrideRepository->findBy(['lodging' => $lodging], ['date' => 'ASC']);
    }
}
