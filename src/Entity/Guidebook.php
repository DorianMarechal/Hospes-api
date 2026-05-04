<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\GuidebookRepository;
use App\State\GuidebookProcessor;
use App\State\PublicGuidebookProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GuidebookRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_GUIDEBOOK_LODGING', fields: ['lodging'])]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/lodgings/{lodgingId}/guidebook',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class)],
            provider: PublicGuidebookProvider::class,
        ),
        new Post(
            uriTemplate: '/me/lodgings/{lodgingId}/guidebook',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class)],
            security: "is_granted('ROLE_HOST')",
            processor: GuidebookProcessor::class,
            read: false,
        ),
        new Patch(
            uriTemplate: '/guidebooks/{id}',
            security: "is_granted('ROLE_HOST')",
            processor: GuidebookProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['guidebook:read']],
    denormalizationContext: ['groups' => ['guidebook:write']],
)]
class Guidebook
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['guidebook:read'])]
    private ?Uuid $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lodging $lodging = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $checkinInstructions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $houseRules = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $wifiName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $wifiPassword = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $localRecommendations = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $emergencyContacts = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $checkoutInstructions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $parkingInfo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['guidebook:read', 'guidebook:write'])]
    private ?string $transportInfo = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['guidebook:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['guidebook:read'])]
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

    public function getCheckinInstructions(): ?string
    {
        return $this->checkinInstructions;
    }

    public function setCheckinInstructions(?string $v): static
    {
        $this->checkinInstructions = $v;

        return $this;
    }

    public function getHouseRules(): ?string
    {
        return $this->houseRules;
    }

    public function setHouseRules(?string $v): static
    {
        $this->houseRules = $v;

        return $this;
    }

    public function getWifiName(): ?string
    {
        return $this->wifiName;
    }

    public function setWifiName(?string $v): static
    {
        $this->wifiName = $v;

        return $this;
    }

    public function getWifiPassword(): ?string
    {
        return $this->wifiPassword;
    }

    public function setWifiPassword(?string $v): static
    {
        $this->wifiPassword = $v;

        return $this;
    }

    public function getLocalRecommendations(): ?string
    {
        return $this->localRecommendations;
    }

    public function setLocalRecommendations(?string $v): static
    {
        $this->localRecommendations = $v;

        return $this;
    }

    public function getEmergencyContacts(): ?string
    {
        return $this->emergencyContacts;
    }

    public function setEmergencyContacts(?string $v): static
    {
        $this->emergencyContacts = $v;

        return $this;
    }

    public function getCheckoutInstructions(): ?string
    {
        return $this->checkoutInstructions;
    }

    public function setCheckoutInstructions(?string $v): static
    {
        $this->checkoutInstructions = $v;

        return $this;
    }

    public function getParkingInfo(): ?string
    {
        return $this->parkingInfo;
    }

    public function setParkingInfo(?string $v): static
    {
        $this->parkingInfo = $v;

        return $this;
    }

    public function getTransportInfo(): ?string
    {
        return $this->transportInfo;
    }

    public function setTransportInfo(?string $v): static
    {
        $this->transportInfo = $v;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $v): static
    {
        $this->createdAt = $v;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $v): static
    {
        $this->updatedAt = $v;

        return $this;
    }
}
