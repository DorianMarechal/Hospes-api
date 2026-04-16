<?php

namespace App\Entity;

use App\Enum\CancellationPolicy;
use App\Enum\LodgingType;
use App\Repository\LodgingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LodgingRepository::class)]
class Lodging
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lodgings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HostProfile $host = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(enumType: LodgingType::class)]
    private ?LodgingType $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $capacity = null;

    #[ORM\Column]
    private ?int $basePriceWeek = null;

    #[ORM\Column]
    private ?int $basePriceWeekend = null;

    #[ORM\Column(nullable: true)]
    private ?int $cleaningFee = null;

    #[ORM\Column(nullable: true)]
    private ?int $touristTaxPerPerson = null;

    #[ORM\Column(nullable: true)]
    private ?int $depositAmount = null;

    #[ORM\Column(enumType: CancellationPolicy::class)]
    private ?CancellationPolicy $cancellationPolicy = null;

    #[ORM\Column(nullable: true)]
    private ?int $minStay = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxStay = null;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    private ?bool $orphanProtection = false;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $checkinTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $checkoutTime = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 10)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 2)]
    private ?string $country = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $averageRating = null;

    #[ORM\Column(nullable: true)]
    private ?int $reviewCount = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, LodgingImage>
     */
    #[ORM\OneToMany(targetEntity: LodgingImage::class, mappedBy: 'lodging', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lodgingImages;

    public function __construct()
    {
        $this->lodgingImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHost(): ?HostProfile
    {
        return $this->host;
    }

    public function setHost(?HostProfile $host): static
    {
        $this->host = $host;

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

    public function getType(): ?LodgingType
    {
        return $this->type;
    }

    public function setType(LodgingType $type): static
    {
        $this->type = $type;

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

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getBasePriceWeek(): ?int
    {
        return $this->basePriceWeek;
    }

    public function setBasePriceWeek(int $basePriceWeek): static
    {
        $this->basePriceWeek = $basePriceWeek;

        return $this;
    }

    public function getBasePriceWeekend(): ?int
    {
        return $this->basePriceWeekend;
    }

    public function setBasePriceWeekend(int $basePriceWeekend): static
    {
        $this->basePriceWeekend = $basePriceWeekend;

        return $this;
    }

    public function getCleaningFee(): ?int
    {
        return $this->cleaningFee;
    }

    public function setCleaningFee(?int $cleaningFee): static
    {
        $this->cleaningFee = $cleaningFee;

        return $this;
    }

    public function getTouristTaxPerPerson(): ?int
    {
        return $this->touristTaxPerPerson;
    }

    public function setTouristTaxPerPerson(?int $touristTaxPerPerson): static
    {
        $this->touristTaxPerPerson = $touristTaxPerPerson;

        return $this;
    }

    public function getDepositAmount(): ?int
    {
        return $this->depositAmount;
    }

    public function setDepositAmount(?int $depositAmount): static
    {
        $this->depositAmount = $depositAmount;

        return $this;
    }

    public function getCancellationPolicy(): ?CancellationPolicy
    {
        return $this->cancellationPolicy;
    }

    public function setCancellationPolicy(CancellationPolicy $cancellationPolicy): static
    {
        $this->cancellationPolicy = $cancellationPolicy;

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

    public function isOrphanProtection(): ?bool
    {
        return $this->orphanProtection;
    }

    public function setOrphanProtection(?bool $orphanProtection): static
    {
        $this->orphanProtection = $orphanProtection;

        return $this;
    }

    public function getCheckinTime(): ?\DateTimeImmutable
    {
        return $this->checkinTime;
    }

    public function setCheckinTime(\DateTimeImmutable $checkinTime): static
    {
        $this->checkinTime = $checkinTime;

        return $this;
    }

    public function getCheckoutTime(): ?\DateTimeImmutable
    {
        return $this->checkoutTime;
    }

    public function setCheckoutTime(\DateTimeImmutable $checkoutTime): static
    {
        $this->checkoutTime = $checkoutTime;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getAverageRating(): ?string
    {
        return $this->averageRating;
    }

    public function setAverageRating(?string $averageRating): static
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    public function getReviewCount(): ?int
    {
        return $this->reviewCount;
    }

    public function setReviewCount(?int $reviewCount): static
    {
        $this->reviewCount = $reviewCount;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    /**
     * @return Collection<int, LodgingImage>
     */
    public function getLodgingImages(): Collection
    {
        return $this->lodgingImages;
    }

    public function addLodgingImage(LodgingImage $lodgingImage): static
    {
        if (!$this->lodgingImages->contains($lodgingImage)) {
            $this->lodgingImages->add($lodgingImage);
            $lodgingImage->setLodging($this);
        }

        return $this;
    }

    public function removeLodgingImage(LodgingImage $lodgingImage): static
    {
        if ($this->lodgingImages->removeElement($lodgingImage)) {
            // set the owning side to null (unless already changed)
            if ($lodgingImage->getLodging() === $this) {
                $lodgingImage->setLodging(null);
            }
        }

        return $this;
    }
}
