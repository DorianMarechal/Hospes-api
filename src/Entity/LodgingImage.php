<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\LodgingImageRepository;
use App\State\LodgingImageProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LodgingImageRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/images',
            security: "is_granted('ROLE_HOST')",
            processor: LodgingImageProcessor::class,
        ),
        new Put(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
        ),
        new Delete(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
        ),
    ],
    normalizationContext: ['groups' => ['lodging_image:read']],
    denormalizationContext: ['groups' => ['lodging_image:write']],
)]
class LodgingImage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['lodging_image:read', 'lodging:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'lodgingImages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Lodging $lodging = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['lodging_image:read', 'lodging_image:write', 'lodging:read'])]
    #[Assert\NotBlank]
    #[Assert\Url]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['lodging_image:read', 'lodging_image:write', 'lodging:read'])]
    private ?string $altText = null;

    #[ORM\Column]
    #[Groups(['lodging_image:read', 'lodging_image:write', 'lodging:read'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $position = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['lodging_image:read'])]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function setAltText(?string $altText): static
    {
        $this->altText = $altText;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
