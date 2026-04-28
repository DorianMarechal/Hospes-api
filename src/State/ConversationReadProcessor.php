<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Conversation;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ConversationReadProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private MessageRepository $messageRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof Conversation);

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        if (!$user->getId()?->equals($data->getCustomer()?->getId())
            && !$user->getId()?->equals($data->getHost()?->getId())) {
            throw new AccessDeniedHttpException('You are not a participant of this conversation');
        }

        $now = new \DateTimeImmutable();
        $messages = $this->messageRepository->findByConversation($data);

        foreach ($messages as $message) {
            if (!$message->isRead() && !$user->getId()->equals($message->getSender()?->getId())) {
                $message->setIsRead(true);
                $message->setReadAt($now);
            }
        }

        $this->entityManager->flush();

        return $data;
    }
}
