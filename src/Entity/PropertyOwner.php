<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Dto\OwnerLodgingRevenue;
use App\Repository\PropertyOwnerRepository;
use App\State\OwnerLodgingRevenueProvider;
use App\State\OwnerLodgingsProvider;
use App\State\OwnerStatementsProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertyOwnerRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_PROPERTY_OWNER_USER', fields: ['user'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/owner/lodgings',
            security: "is_granted('ROLE_USER')",
            provider: OwnerLodgingsProvider::class,
            normalizationContext: ['groups' => ['lodging:list']],
        ),
        new GetCollection(
            uriTemplate: '/owner/statements',
            security: "is_granted('ROLE_USER')",
            provider: OwnerStatementsProvider::class,
            normalizationContext: ['groups' => ['owner_statement:read']],
        ),
        new Get(
            uriTemplate: '/owner/lodgings/{lodgingId}/revenue',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class)],
            security: "is_granted('ROLE_USER')",
            provider: OwnerLodgingRevenueProvider::class,
            output: OwnerLodgingRevenue::class,
            normalizationContext: ['groups' => ['owner_revenue:read']],
        ),
    ],
    normalizationContext: ['groups' => ['property_owner:read']],
)]
class PropertyOwner
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['property_owner:read'])]
    private ?Uuid $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['property_owner:read'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Groups(['property_owner:read'])]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $commissionRate = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $paymentDetails = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['property_owner:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCommissionRate(): ?string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(string $commissionRate): static
    {
        $this->commissionRate = $commissionRate;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPaymentDetails(): ?array
    {
        return $this->paymentDetails;
    }

    /**
     * @param array<string, mixed>|null $paymentDetails
     */
    public function setPaymentDetails(?array $paymentDetails): static
    {
        $this->paymentDetails = $paymentDetails;

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
