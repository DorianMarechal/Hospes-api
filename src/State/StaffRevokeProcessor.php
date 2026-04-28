<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\StaffAssignment;
use Doctrine\ORM\EntityManagerInterface;

class StaffRevokeProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StaffAssignment
    {
        $assignment = $context['previous_data'];
        \assert($assignment instanceof StaffAssignment);

        $assignment->setIsRevoked(true);
        $assignment->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $assignment;
    }
}
