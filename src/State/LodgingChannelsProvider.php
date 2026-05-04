<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\ChannelConnectionRepository;
use App\Repository\LodgingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LodgingChannelsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private ChannelConnectionRepository $channelConnectionRepository,
    ) {
    }

    /**
     * @return \App\Entity\ChannelConnection[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        $hostProfile = $user->getHostProfile();
        if (!$hostProfile || !$lodging->getHost()?->getId()?->equals($hostProfile->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging');
        }

        return $this->channelConnectionRepository->findBy(['lodging' => $lodging]);
    }
}
