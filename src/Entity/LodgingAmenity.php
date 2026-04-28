<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\State\LodgingAmenityProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_lodging_amenity', columns: ['lodging_id', 'amenity_id'])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/amenities',
            security: "is_granted('ROLE_HOST')",
            processor: LodgingAmenityProcessor::class,
        ),
        new Delete(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
        ),
    ],
    normalizationContext: ['groups' => ['lodging_amenity:read']],
    denormalizationContext: ['groups' => ['lodging_amenity:write']],
)]
class LodgingAmenity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['lodging_amenity:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Lodging $lodging = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['lodging_amenity:read', 'lodging_amenity:write'])]
    private ?Amenity $amenity = null;

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getAmenity(): ?Amenity
    {
        return $this->amenity;
    }

    public function setAmenity(?Amenity $amenity): static
    {
        $this->amenity = $amenity;

        return $this;
    }
}
