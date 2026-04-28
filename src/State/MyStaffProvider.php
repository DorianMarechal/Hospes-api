<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\StaffAssignmentRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MyStaffProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private StaffAssignmentRepository $staffAssignmentRepository,
    ) {
    }

    /**
     * @return \App\Entity\StaffAssignment[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        return $this->staffAssignmentRepository->findByHost($user);
    }
}
