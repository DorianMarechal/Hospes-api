<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\BookingStatus;
use App\Enum\CancellationPolicy;
use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ApiResource(operations: [])]
class Booking
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lodging $lodging = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $checkin = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $checkout = null;

    #[ORM\Column]
    private ?int $guestsCount = null;

    #[ORM\Column]
    private ?int $numberOfNights = null;

    #[ORM\Column]
    private ?int $nightsTotal = null;

    #[ORM\Column]
    private ?int $cleaningFee = null;

    #[ORM\Column]
    private ?int $touristTaxTotal = null;

    #[ORM\Column]
    private ?int $depositAmount = null;

    #[ORM\Column]
    private ?int $totalPrice = null;

    #[ORM\Column(enumType: CancellationPolicy::class)]
    private ?CancellationPolicy $cancellationPolicy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $cancelledBy = null;

    #[ORM\Column(enumType: BookingStatus::class)]
    private ?BookingStatus $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, BookingNight>
     */
    #[ORM\OneToMany(targetEntity: BookingNight::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bookingNights;

    public function __construct()
    {
        $this->bookingNights = new ArrayCollection();
    }

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

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCheckin(): ?\DateTimeImmutable
    {
        return $this->checkin;
    }

    public function setCheckin(\DateTimeImmutable $checkin): static
    {
        $this->checkin = $checkin;

        return $this;
    }

    public function getCheckout(): ?\DateTimeImmutable
    {
        return $this->checkout;
    }

    public function setCheckout(\DateTimeImmutable $checkout): static
    {
        $this->checkout = $checkout;

        return $this;
    }

    public function getGuestsCount(): ?int
    {
        return $this->guestsCount;
    }

    public function setGuestsCount(int $guestsCount): static
    {
        $this->guestsCount = $guestsCount;

        return $this;
    }

    public function getNumberOfNights(): ?int
    {
        return $this->numberOfNights;
    }

    public function setNumberOfNights(int $numberOfNights): static
    {
        $this->numberOfNights = $numberOfNights;

        return $this;
    }

    public function getNightsTotal(): ?int
    {
        return $this->nightsTotal;
    }

    public function setNightsTotal(int $nightsTotal): static
    {
        $this->nightsTotal = $nightsTotal;

        return $this;
    }

    public function getCleaningFee(): ?int
    {
        return $this->cleaningFee;
    }

    public function setCleaningFee(int $cleaningFee): static
    {
        $this->cleaningFee = $cleaningFee;

        return $this;
    }

    public function getTouristTaxTotal(): ?int
    {
        return $this->touristTaxTotal;
    }

    public function setTouristTaxTotal(int $touristTaxTotal): static
    {
        $this->touristTaxTotal = $touristTaxTotal;

        return $this;
    }

    public function getDepositAmount(): ?int
    {
        return $this->depositAmount;
    }

    public function setDepositAmount(int $depositAmount): static
    {
        $this->depositAmount = $depositAmount;

        return $this;
    }

    public function getTotalPrice(): ?int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

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

    public function getCancelledBy(): ?User
    {
        return $this->cancelledBy;
    }

    public function setCancelledBy(?User $cancelledBy): static
    {
        $this->cancelledBy = $cancelledBy;

        return $this;
    }

    public function getStatus(): ?BookingStatus
    {
        return $this->status;
    }

    public function setStatus(BookingStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

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
     * @return Collection<int, BookingNight>
     */
    public function getBookingNights(): Collection
    {
        return $this->bookingNights;
    }

    public function addBookingNight(BookingNight $bookingNight): static
    {
        if (!$this->bookingNights->contains($bookingNight)) {
            $this->bookingNights->add($bookingNight);
            $bookingNight->setBooking($this);
        }

        return $this;
    }

    public function removeBookingNight(BookingNight $bookingNight): static
    {
        if ($this->bookingNights->removeElement($bookingNight)) {
            // set the owning side to null (unless already changed)
            if ($bookingNight->getBooking() === $this) {
                $bookingNight->setBooking(null);
            }
        }

        return $this;
    }
}
