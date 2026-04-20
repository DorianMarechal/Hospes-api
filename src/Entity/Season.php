<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SeasonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
#[ApiResource]
class Season
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'seasons')]
    #[JoinColumn(nullable: false)]
    private ?Lodging $lodging = null;

    #[ORM\Column(length: 80)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column]
    private ?int $priceWeek = null;

    #[ORM\Column]
    private ?int $priceWeekend = null;

    #[ORM\Column(nullable: true)]
    private ?int $minStay = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxStay = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
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
