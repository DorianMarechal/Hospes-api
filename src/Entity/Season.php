<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\SeasonRepository;
use App\State\SeasonCollectionProvider;
use App\State\SeasonProcessor;
use App\Validator\NoSeasonOverlap;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
#[ORM\Index(columns: ['lodging_id', 'start_date', 'end_date'], name: 'idx_season_lodging_dates')]
#[NoSeasonOverlap]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/seasons',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            provider: SeasonCollectionProvider::class
        ),
        new Get(
            security: "is_granted('LODGING_EDIT', object.getLodging())"
        ),
        new Post(
            security: "is_granted('ROLE_HOST')",
            uriTemplate: '/lodgings/{lodgingId}/seasons',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            processor: SeasonProcessor::class
        ),
        new Put(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
            processor: SeasonProcessor::class
        ),
        new Delete(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
        ),
    ],
    normalizationContext: ['groups' => ['season:read']],
    denormalizationContext: ['groups' => ['season:write']]
)]
class Season
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['season:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'seasons')]
    #[JoinColumn(nullable: false)]
    #[Groups(['season:read'])]
    private ?Lodging $lodging = null;

    #[ORM\Column(length: 80)]
    #[Groups(['season:read', 'season:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['season:read', 'season:write'])]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['season:read', 'season:write'])]
    #[Assert\NotNull]
    #[Assert\GreaterThan(propertyPath: 'startDate')]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column]
    #[Groups(['season:read', 'season:write'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $priceWeek = null;

    #[ORM\Column]
    #[Groups(['season:read', 'season:write'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $priceWeekend = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['season:read', 'season:write'])]
    #[Assert\Positive]
    private ?int $minStay = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['season:read', 'season:write'])]
    #[Assert\Positive]
    #[Assert\GreaterThanOrEqual(propertyPath: 'minStay')]
    private ?int $maxStay = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['season:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['season:read'])]
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getPriceWeek(): ?int
    {
        return $this->priceWeek;
    }

    public function setPriceWeek(int $priceWeek): static
    {
        $this->priceWeek = $priceWeek;

        return $this;
    }

    public function getPriceWeekend(): ?int
    {
        return $this->priceWeekend;
    }

    public function setPriceWeekend(int $priceWeekend): static
    {
        $this->priceWeekend = $priceWeekend;

        return $this;
    }

    public function getMinStay(): ?int
    {
        return $this->minStay;
    }

    public function setMinStay(?int $minStay): static
    {
        $this->minStay = $minStay;

        return $this;
    }

    public function getMaxStay(): ?int
    {
        return $this->maxStay;
    }

    public function setMaxStay(?int $maxStay): static
    {
        $this->maxStay = $maxStay;

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
