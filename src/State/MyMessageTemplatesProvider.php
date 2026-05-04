<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\MessageTemplateRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MyMessageTemplatesProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private MessageTemplateRepository $messageTemplateRepository,
    ) {
    }

    /**
     * @return \App\Entity\MessageTemplate[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $hostProfile = $user->getHostProfile();

        if (null === $hostProfile) {
            return [];
        }

        return $this->messageTemplateRepository->findByHostProfile($hostProfile);
    }
}
