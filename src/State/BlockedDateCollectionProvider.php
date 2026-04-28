<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\LodgingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlockedDateCollectionProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);

        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        if (null === $lodging->getHost() || !$lodging->getHost()->getId()?->equals($user->getHostProfile()?->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging');
        }

        return $lodging->getBlockedDates()->toArray();
    }
}
