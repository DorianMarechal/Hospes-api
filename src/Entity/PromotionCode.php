<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\PromotionType;
use App\Repository\PromotionCodeRepository;
use App\State\MyPromotionCodesProvider;
use App\State\PromotionCodeProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PromotionCodeRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_PROMO_CODE', fields: ['code'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/me/promotion-codes',
            security: "is_granted('ROLE_HOST')",
            provider: MyPromotionCodesProvider::class,
        ),
        new Post(
            uriTemplate: '/me/promotion-codes',
            security: "is_granted('ROLE_HOST')",
            processor: PromotionCodeProcessor::class,
            read: false,
        ),
        new Patch(
            uriTemplate: '/promotion-codes/{id}',
            security: "is_granted('ROLE_HOST')",
            processor: PromotionCodeProcessor::class,
        ),
        new Delete(
            uriTemplate: '/promotion-codes/{id}',
            security: "is_granted('ROLE_HOST')",
        ),
    ],
    normalizationContext: ['groups' => ['promo:read']],
    denormalizationContext: ['groups' => ['promo:write']],
)]
class PromotionCode
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['promo:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?HostProfile $hostProfile = null;

    #[ORM\Column(length: 30)]
    #[Groups(['promo:read', 'promo:write'])]
    #[Assert\NotBlank]
    private ?string $code = null;

    #[ORM\Column(length: 10, enumType: PromotionType::class)]
    #[Groups(['promo:read', 'promo:write'])]
    #[Assert\NotNull]
    private ?PromotionType $type = null;

    #[ORM\Column]
    #[Groups(['promo:read', 'promo:write'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $value = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['promo:read', 'promo:write'])]
    #[Assert\Positive]
    private ?int $maxUses = null;

    #[ORM\Column]
    #[Groups(['promo:read'])]
    private int $usesCount = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['promo:read', 'promo:write'])]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['promo:read', 'promo:write'])]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['promo:read', 'promo:write'])]
    private ?Lodging $lodging = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['promo:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getHostProfile(): ?HostProfile
    {
        return $this->hostProfile;
    }

    public function setHostProfile(?HostProfile $hostProfile): static
    {
        $this->hostProfile = $hostProfile;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function getType(): ?PromotionType
    {
        return $this->type;
    }

    public function setType(PromotionType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getMaxUses(): ?int
    {
        return $this->maxUses;
    }

    public function setMaxUses(?int $maxUses): static
    {
        $this->maxUses = $maxUses;

        return $this;
    }

    public function getUsesCount(): int
    {
        return $this->usesCount;
    }

    public function incrementUsesCount(): static
    {
        ++$this->usesCount;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTimeImmutable $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(?\DateTimeImmutable $validTo): static
    {
        $this->validTo = $validTo;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isUsable(): bool
    {
        $now = new \DateTimeImmutable();

        if (null !== $this->validFrom && $now < $this->validFrom) {
            return false;
        }

        if (null !== $this->validTo && $now > $this->validTo) {
            return false;
        }

        if (null !== $this->maxUses && $this->usesCount >= $this->maxUses) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $totalPrice): int
    {
        if (null === $this->type || null === $this->value) {
            return 0;
        }

        return match ($this->type) {
            PromotionType::PERCENT => (int) round($totalPrice * $this->value / 100),
            PromotionType::FIXED => min($this->value, $totalPrice),
        };
    }
}
