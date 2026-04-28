<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\UpdateStaffLodgingsRequest;
use App\Entity\StaffAssignment;
use App\Entity\StaffLodging;
use App\Repository\LodgingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StaffLodgingsProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private LodgingRepository $lodgingRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StaffAssignment
    {
        assert($data instanceof UpdateStaffLodgingsRequest);

        $assignment = $context['previous_data'];
        \assert($assignment instanceof StaffAssignment);

        $host = $assignment->getHost();
        $hostProfile = $host?->getHostProfile();

        $assignment->clearLodgings();
        $this->em->flush();

        foreach ($data->lodgingIds as $lodgingId) {
            $lodging = $this->lodgingRepository->find($lodgingId);
            if (!$lodging) {
                throw new BadRequestHttpException(sprintf('Lodging %s not found', $lodgingId));
            }
            if (!$lodging->getHost()?->getId()?->equals($hostProfile?->getId())) {
                throw new BadRequestHttpException(sprintf('Lodging %s does not belong to the host', $lodgingId));
            }
            $sl = new StaffLodging();
            $sl->setLodging($lodging);
            $assignment->addLodging($sl);
        }

        $assignment->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $assignment;
    }
}
