<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Service\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MessageProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private ConversationRepository $conversationRepository,
        private EntityManagerInterface $entityManager,
        private MercurePublisher $mercurePublisher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Message) {
            throw new \InvalidArgumentException('Expected '.Message::class);
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $conversation = $this->conversationRepository->find($uriVariables['conversationId']);
        if (!$conversation) {
            throw new NotFoundHttpException('Conversation not found');
        }

        if (!$user->getId()?->equals($conversation->getCustomer()?->getId())
            && !$user->getId()?->equals($conversation->getHost()?->getId())) {
            throw new AccessDeniedHttpException('You are not a participant of this conversation');
        }

        $now = new \DateTimeImmutable();

        $data->setConversation($conversation);
        $data->setSender($user);
        $data->setCreatedAt($now);

        $conversation->setUpdatedAt($now);

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->mercurePublisher->publishMessage($data);

        return $data;
    }
}
