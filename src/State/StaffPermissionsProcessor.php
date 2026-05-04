<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\UpdateStaffPermissionsRequest;
use App\Entity\StaffAssignment;
use App\Entity\StaffPermission;
use App\Enum\StaffPermission as StaffPermissionEnum;
use Doctrine\ORM\EntityManagerInterface;

class StaffPermissionsProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StaffAssignment
    {
        if (!$data instanceof UpdateStaffPermissionsRequest) {
            throw new \InvalidArgumentException('Expected '.UpdateStaffPermissionsRequest::class);
        }

        $assignment = $context['previous_data'];
        if (!$assignment instanceof StaffAssignment) {
            throw new \InvalidArgumentException('Expected '.StaffAssignment::class);
        }

        $assignment->clearPermissions();
        $this->em->flush();

        foreach ($data->permissions as $permissionName) {
            $perm = StaffPermissionEnum::from($permissionName);
            $sp = new StaffPermission();
            $sp->setPermission($perm);
            $assignment->addPermission($sp);
        }

        $assignment->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $assignment;
    }
}
