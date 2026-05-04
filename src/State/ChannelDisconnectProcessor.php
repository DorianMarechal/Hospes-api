<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ChannelConnection;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ChannelDisconnectProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
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

        $this->em->remove($data);
        $this->em->flush();

        return null;
    }
}
