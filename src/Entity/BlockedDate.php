<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\BlockedDateRepository;
use App\State\BlockedDateCollectionProvider;
use App\State\BlockedDateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BlockedDateRepository::class)]
#[ORM\Index(columns: ['lodging_id', 'start_date', 'end_date'], name: 'idx_blocked_date_lodging_dates')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/blocked-dates',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            provider: BlockedDateCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/blocked-dates',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            processor: BlockedDateProcessor::class,
        ),
        new Delete(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
        ),
    ],
    normalizationContext: ['groups' => ['blocked_date:read']],
    denormalizationContext: ['groups' => ['blocked_date:write']],
)]
class BlockedDate
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['blocked_date:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'blockedDates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lodging $lodging = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['blocked_date:read', 'blocked_date:write'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['blocked_date:read', 'blocked_date:write'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['blocked_date:read', 'blocked_date:write'])]
    private ?string $reason = null;

    #[ORM\Column(length: 10)]
    #[Groups(['blocked_date:read'])]
    private ?string $source = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['blocked_date:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['blocked_date:read'])]
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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

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
