<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConversationMessagesProvider implements ProviderInterface
{
    public function __construct(
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $conversation = $this->conversationRepository->find($uriVariables['conversationId']);

        if (!$conversation) {
            throw new NotFoundHttpException('Conversation not found');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        if (!$user->getId()?->equals($conversation->getCustomer()?->getId())
            && !$user->getId()?->equals($conversation->getHost()?->getId())) {
            throw new AccessDeniedHttpException('You are not a participant of this conversation');
        }

        return $this->messageRepository->findByConversation($conversation);
    }
}
