<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\IcalFeedRepository;
use App\Repository\LodgingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IcalFeedCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private IcalFeedRepository $icalFeedRepository,
    ) {
    }

    /**
     * @return \App\Entity\IcalFeed[]
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

        return $this->icalFeedRepository->findByLodging($lodging);
    }
}
