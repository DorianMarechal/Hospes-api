<?php

namespace App\Entity;

use App\Enum\StaffPermission as StaffPermissionEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class StaffPermission
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StaffAssignment $staffAssignment = null;

    #[ORM\Column(length: 30, enumType: StaffPermissionEnum::class)]
    #[Groups(['staff:read'])]
    private ?StaffPermissionEnum $permission = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getStaffAssignment(): ?StaffAssignment
    {
        return $this->staffAssignment;
    }

    public function setStaffAssignment(?StaffAssignment $staffAssignment): static
    {
        $this->staffAssignment = $staffAssignment;

        return $this;
    }

    public function getPermission(): ?StaffPermissionEnum
    {
        return $this->permission;
    }

    public function setPermission(StaffPermissionEnum $permission): static
    {
        $this->permission = $permission;

        return $this;
    }
}
