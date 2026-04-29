<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\PriceOverrideRepository;
use App\State\PriceOverrideCollectionProvider;
use App\State\PriceOverrideProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PriceOverrideRepository::class)]
#[ORM\UniqueConstraint(columns: ['lodging_id', 'date'], name: 'uniq_price_override_lodging_date')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/price-overrides',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            provider: PriceOverrideCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/price-overrides',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            processor: PriceOverrideProcessor::class,
        ),
        new Put(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
        ),
        new Delete(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
        ),
    ],
    normalizationContext: ['groups' => ['price_override:read']],
    denormalizationContext: ['groups' => ['price_override:write']],
)]
class PriceOverride
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['price_override:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'priceOverrides')]
    #[JoinColumn(nullable: false)]
    private ?Lodging $lodging = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['price_override:read', 'price_override:write'])]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    #[Groups(['price_override:read', 'price_override:write'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $price = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['price_override:read', 'price_override:write'])]
    private ?string $label = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['price_override:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
