<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ExtraRepository;
use App\State\ExtraProcessor;
use App\State\LodgingExtrasProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExtraRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/extras',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            provider: LodgingExtrasProvider::class,
        ),
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/extras',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            processor: ExtraProcessor::class,
            read: false,
        ),
        new Patch(
            uriTemplate: '/extras/{id}',
            security: "is_granted('ROLE_HOST')",
            processor: ExtraProcessor::class,
        ),
        new Delete(
            uriTemplate: '/extras/{id}',
            security: "is_granted('ROLE_HOST')",
        ),
    ],
    normalizationContext: ['groups' => ['extra:read']],
    denormalizationContext: ['groups' => ['extra:write']],
)]
class Extra
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['extra:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lodging $lodging = null;

    #[ORM\Column(length: 100)]
    #[Groups(['extra:read', 'extra:write'])]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['extra:read', 'extra:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['extra:read', 'extra:write'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $price = null;

    #[ORM\Column(length: 15)]
    #[Groups(['extra:read', 'extra:write'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['per_booking', 'per_night', 'per_person'])]
    private string $priceType = 'per_booking';

    #[ORM\Column]
    #[Groups(['extra:read', 'extra:write'])]
    private bool $enabled = true;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['extra:read'])]
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getPriceType(): string
    {
        return $this->priceType;
    }

    public function setPriceType(string $priceType): static
    {
        $this->priceType = $priceType;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

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

    public function calculateTotal(int $nights, int $guests): int
    {
        $price = $this->price ?? 0;

        return match ($this->priceType) {
            'per_night' => $price * $nights,
            'per_person' => $price * $guests,
            default => $price,
        };
    }
}
