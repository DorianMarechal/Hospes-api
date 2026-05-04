<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ConversationRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MyConversationsProvider implements ProviderInterface
{
    public function __construct(
        private ConversationRepository $conversationRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        return $this->conversationRepository->findByParticipant($user);
    }
}
