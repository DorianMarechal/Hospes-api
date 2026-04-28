<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use App\Repository\LodgingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConversationProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private ConversationRepository $conversationRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof Conversation);

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $host = $lodging->getHost()?->getUser();
        if (!$host) {
            throw new NotFoundHttpException('Host not found for this lodging');
        }

        $existing = $this->conversationRepository->findOneBy([
            'lodging' => $lodging,
            'customer' => $user,
        ]);
        if ($existing) {
            throw new HttpException(409, 'A conversation already exists for this lodging');
        }

        $now = new \DateTimeImmutable();

        $data->setLodging($lodging);
        $data->setCustomer($user);
        $data->setHost($host);
        $data->setCreatedAt($now);
        $data->setUpdatedAt($now);

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
