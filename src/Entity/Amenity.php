<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['amenity:read']],
)]
class Amenity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['amenity:read', 'lodging:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 80, unique: true)]
    #[Groups(['amenity:read', 'lodging:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['amenity:read', 'lodging:read'])]
    private ?string $icon = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }
}
