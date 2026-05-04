<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Lodging;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GeneratePricingApiKeyProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Lodging
    {
        if (!$data instanceof Lodging) {
            throw new \InvalidArgumentException('Expected '.Lodging::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        $hostProfile = $user->getHostProfile();
        if (!$hostProfile || !$data->getHost()?->getId()?->equals($hostProfile->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging');
        }

        $apiKey = bin2hex(random_bytes(32));
        $data->setPricingWebhookApiKey($apiKey);
        $data->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $data;
    }
}
