<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_staff_lodging', columns: ['staff_assignment_id', 'lodging_id'])]
class StaffLodging
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'lodgings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StaffAssignment $staffAssignment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['staff:read'])]
    private ?Lodging $lodging = null;

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

    public function getLodging(): ?Lodging
    {
        return $this->lodging;
    }

    public function setLodging(?Lodging $lodging): static
    {
        $this->lodging = $lodging;

        return $this;
    }
}
