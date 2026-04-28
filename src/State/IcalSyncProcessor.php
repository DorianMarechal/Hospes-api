<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\IcalFeed;
use App\Enum\IcalDirection;
use App\Service\IcalSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IcalSyncProcessor implements ProcessorInterface
{
    public function __construct(
        private IcalSyncService $icalSyncService,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): IcalFeed
    {
        $feed = $context['previous_data'];
        \assert($feed instanceof IcalFeed);

        if (IcalDirection::IMPORT !== $feed->getDirection()) {
            throw new BadRequestHttpException('Only import feeds can be synced');
        }

        $this->icalSyncService->sync($feed);

        $feed->setLastSyncAt(new \DateTimeImmutable());
        $this->em->flush();

        return $feed;
    }
}
