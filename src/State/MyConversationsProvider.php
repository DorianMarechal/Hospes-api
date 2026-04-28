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

        $asCustomer = $this->conversationRepository->findByCustomer($user);
        $asHost = $this->conversationRepository->findByHost($user);

        $conversations = array_merge($asCustomer, $asHost);

        usort($conversations, fn ($a, $b) => $b->getUpdatedAt() <=> $a->getUpdatedAt());

        return array_values(array_unique($conversations, \SORT_REGULAR));
    }
}
