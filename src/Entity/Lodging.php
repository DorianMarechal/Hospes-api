<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\CancellationPolicy;
use App\Enum\LodgingType;
use App\Repository\LodgingRepository;
use App\State\LodgingProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LodgingRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(
            security: "is_granted('ROLE_HOST')",
            processor: LodgingProcessor::class
        ),
        new Patch(
            security: "is_granted('LODGING_EDIT', object)",
            processor: LodgingProcessor::class
        ),
        new Delete(
            security: "is_granted('LODGING_DELETE', object)"
        ),
    ],
    normalizationContext: ['groups' => ['lodging:read']],
    denormalizationContext: ['groups' => ['lodging:write']]
)]
class Lodging
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['lodging:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'lodgings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HostProfile $host = null;

    #[ORM\Column(length: 150)]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private ?string $name = null;

    #[ORM\Column(enumType: LodgingType::class)]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\NotNull]
    private ?LodgingType $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $capacity = null;

    #[ORM\Column]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $basePriceWeek = null;

    #[ORM\Column]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $basePriceWeekend = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\PositiveOrZero]
    private ?int $cleaningFee = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\PositiveOrZero]
    private ?int $touristTaxPerPerson = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\PositiveOrZero]
    private ?int $depositAmount = null;

    #[ORM\Column(enumType: CancellationPolicy::class)]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\NotNull]
    private ?CancellationPolicy $cancellationPolicy = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\Positive]
    private ?int $minStay = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    #[Assert\Positive]
    #[Assert\GreaterThanOrEqual(propertyPath: 'minStay')]
    private ?int $maxStay = null;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?bool $orphanProtection = false;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?\DateTimeImmutable $checkinTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?\DateTimeImmutable $checkoutTime = null;

    #[ORM\Column(length: 255)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?string $region = null;

    #[ORM\Column(length: 10)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 2)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?string $country = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    #[Groups(['lodging:read', 'lodging:write'])]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    #[Groups(['lodging:read'])]
    private ?string $averageRating = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['lodging:read'])]
    private ?int $reviewCount = null;

    #[ORM\Column]
    #[Groups(['lodging:read'])]
    private ?bool $isActive = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['lodging:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['lodging:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, LodgingImage>
     */
    #[ORM\OneToMany(targetEntity: LodgingImage::class, mappedBy: 'lodging', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Groups(['lodging:read'])]
    private Collection $lodgingImages;

    /**
     * @var Collection<int, PriceOverride>
     */
    #[ORM\OneToMany(targetEntity: PriceOverride::class, mappedBy: 'lodging', cascade: ['persist', 'remove'])]
    private Collection $priceOverrides;

    /**
     * @var Collection<int, Season>
     */
    #[ORM\OneToMany(targetEntity: Season::class, mappedBy: 'lodging', cascade: ['persist', 'remove'])]
    private Collection $seasons;

    /**
     * @var Collection<int, BlockedDate>
     */
    #[ORM\OneToMany(targetEntity: BlockedDate::class, mappedBy: 'lodging', orphanRemoval: true)]
    private Collection $blockedDates;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'lodging')]
    private Collection $bookings;

    public function __construct()
    {
        $this->lodgingImages = new ArrayCollection();
        $this->priceOverrides = new ArrayCollection();
        $this->seasons = new ArrayCollection();
        $this->blockedDates = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?Uuid
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

    public function getIsActive(): ?bool
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

    /**
     * @return Collection<int, PriceOverride>
     */
    public function getPriceOverrides(): Collection
    {
        return $this->priceOverrides;
    }

    public function addPriceOverride(PriceOverride $priceOverride): static
    {
        if (!$this->priceOverrides->contains($priceOverride)) {
            $this->priceOverrides->add($priceOverride);
            $priceOverride->setLodging($this);
        }

        return $this;
    }

    public function removePriceOverride(PriceOverride $priceOverride): static
    {
        if ($this->priceOverrides->removeElement($priceOverride)) {
            // set the owning side to null (unless already changed)
            if ($priceOverride->getLodging() === $this) {
                $priceOverride->setLodging(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function addSeason(Season $season): static
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
            $season->setLodging($this);
        }

        return $this;
    }

    public function removeSeason(Season $season): static
    {
        if ($this->seasons->removeElement($season)) {
            // set the owning side to null (unless already changed)
            if ($season->getLodging() === $this) {
                $season->setLodging(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BlockedDate>
     */
    public function getBlockedDates(): Collection
    {
        return $this->blockedDates;
    }

    public function addBlockedDate(BlockedDate $blockedDate): static
    {
        if (!$this->blockedDates->contains($blockedDate)) {
            $this->blockedDates->add($blockedDate);
            $blockedDate->setLodging($this);
        }

        return $this;
    }

    public function removeBlockedDate(BlockedDate $blockedDate): static
    {
        if ($this->blockedDates->removeElement($blockedDate)) {
            // set the owning side to null (unless already changed)
            if ($blockedDate->getLodging() === $this) {
                $blockedDate->setLodging(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setLodging($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getLodging() === $this) {
                $booking->setLodging(null);
            }
        }

        return $this;
    }
}
