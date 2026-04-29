<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Dto\BookingRequest;
use App\Dto\CancelBookingRequest;
use App\Dto\ModifyBookingDatesRequest;
use App\Enum\BookingStatus;
use App\Enum\CancellationPolicy;
use App\Repository\BookingRepository;
use App\State\BookingByLodgingProvider;
use App\State\BookingByReferenceProvider;
use App\State\BookingCancelProcessor;
use App\State\BookingConfirmProcessor;
use App\State\BookingCreateProcessor;
use App\State\BookingHistoryProvider;
use App\State\BookingModifyDatesProcessor;
use App\State\BookingNightsProvider;
use App\State\MyBookingsProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/bookings',
            input: BookingRequest::class,
            security: "is_granted('ROLE_CUSTOMER')",
            processor: BookingCreateProcessor::class,
        ),
        new Get(
            uriTemplate: '/bookings/{id}',
            security: "is_granted('BOOKING_VIEW', object)",
        ),
        new GetCollection(
            uriTemplate: '/bookings',
            provider: BookingByReferenceProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/me/bookings',
            security: "is_granted('ROLE_CUSTOMER')",
            provider: MyBookingsProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/bookings',
            security: "is_granted('ROLE_HOST')",
            provider: BookingByLodgingProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/bookings/{id}/nights',
            provider: BookingNightsProvider::class,
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['booking_night:read']],
        ),
        new GetCollection(
            uriTemplate: '/bookings/{id}/history',
            security: "is_granted('BOOKING_VIEW', object)",
            provider: BookingHistoryProvider::class,
            normalizationContext: ['groups' => ['booking_history:read']],
        ),
        new Post(
            uriTemplate: '/bookings/{id}/confirm',
            security: "is_granted('BOOKING_VIEW', object)",
            input: false,
            processor: BookingConfirmProcessor::class,
        ),
        new Post(
            uriTemplate: '/bookings/{id}/cancel',
            input: CancelBookingRequest::class,
            security: "is_granted('BOOKING_CANCEL', object)",
            processor: BookingCancelProcessor::class,
        ),
        new Put(
            uriTemplate: '/bookings/{id}/dates',
            input: ModifyBookingDatesRequest::class,
            security: "is_granted('BOOKING_EDIT', object)",
            processor: BookingModifyDatesProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['booking:read']],
    denormalizationContext: ['groups' => ['booking:write']],
)]
class Booking
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['booking:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read'])]
    private ?Lodging $lodging = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read'])]
    private ?User $customer = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Groups(['booking:read'])]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['booking:read'])]
    private ?\DateTimeImmutable $checkin = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['booking:read'])]
    private ?\DateTimeImmutable $checkout = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $guestsCount = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $numberOfNights = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $nightsTotal = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $cleaningFee = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $touristTaxTotal = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $depositAmount = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $totalPrice = null;

    #[ORM\Column(enumType: CancellationPolicy::class)]
    #[Groups(['booking:read'])]
    private ?CancellationPolicy $cancellationPolicy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['booking:read'])]
    private ?User $cancelledBy = null;

    #[ORM\Column(enumType: BookingStatus::class)]
    #[Groups(['booking:read'])]
    private ?BookingStatus $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['booking:read'])]
    private ?string $cancellationReason = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['booking:read'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['booking:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['booking:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, BookingNight>
     */
    #[ORM\OneToMany(targetEntity: BookingNight::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['booking:read'])]
    private Collection $bookingNights;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'booking')]
    private Collection $payments;

    #[ORM\OneToOne(mappedBy: 'booking', cascade: ['persist'])]
    #[Groups(['booking:read'])]
    private ?Deposit $deposit = null;

    public function __construct()
    {
        $this->bookingNights = new ArrayCollection();
        $this->payments = new ArrayCollection();
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

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
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

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setBooking($this);
        }

        return $this;
    }

    public function getDeposit(): ?Deposit
    {
        return $this->deposit;
    }

    public function setDeposit(?Deposit $deposit): static
    {
        if (null !== $deposit && $deposit->getBooking() !== $this) {
            $deposit->setBooking($this);
        }
        $this->deposit = $deposit;

        return $this;
    }
}
