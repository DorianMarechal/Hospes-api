<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ChannelConnection;
use App\Entity\User;
use App\Service\ChannelSyncService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ChannelSyncProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private ChannelSyncService $channelSyncService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ChannelConnection
    {
        if (!$data instanceof ChannelConnection) {
            throw new \InvalidArgumentException('Expected '.ChannelConnection::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        $hostProfile = $user->getHostProfile();
        $lodging = $data->getLodging();

        if (!$hostProfile || !$lodging?->getHost()?->getId()?->equals($hostProfile->getId())) {
            throw new AccessDeniedHttpException('You do not own this channel connection');
        }

        $this->channelSyncService->sync($data);

        return $data;
    }
}
