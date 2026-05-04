<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Dto\CreateModificationRequestInput;
use App\Enum\ModificationRequestStatus;
use App\Repository\BookingModificationRequestRepository;
use App\State\ModificationRequestAcceptProcessor;
use App\State\ModificationRequestCreateProcessor;
use App\State\ModificationRequestRejectProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookingModificationRequestRepository::class)]
#[ORM\Index(columns: ['booking_id', 'status'], name: 'idx_modification_request_booking_status')]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/bookings/{bookingId}/modification-request',
            uriVariables: ['bookingId' => new Link(fromClass: Booking::class)],
            input: CreateModificationRequestInput::class,
            denormalizationContext: [],
            security: "is_granted('ROLE_USER')",
            processor: ModificationRequestCreateProcessor::class,
        ),
        new Get(
            uriTemplate: '/booking-modifications/{id}',
            security: "is_granted('MODIFICATION_VIEW', object)",
        ),
        new GetCollection(
            uriTemplate: '/bookings/{bookingId}/modification-requests',
            uriVariables: ['bookingId' => new Link(fromClass: Booking::class, toProperty: 'booking')],
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: '/booking-modifications/{id}/accept',
            security: "is_granted('MODIFICATION_RESPOND', object)",
            input: false,
            processor: ModificationRequestAcceptProcessor::class,
        ),
        new Post(
            uriTemplate: '/booking-modifications/{id}/reject',
            security: "is_granted('MODIFICATION_RESPOND', object)",
            input: false,
            processor: ModificationRequestRejectProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['modification_request:read']],
)]
class BookingModificationRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['modification_request:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['modification_request:read'])]
    private ?Booking $booking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['modification_request:read'])]
    private ?User $requestedBy = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['modification_request:read'])]
    private ?\DateTimeImmutable $proposedCheckin = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['modification_request:read'])]
    private ?\DateTimeImmutable $proposedCheckout = null;

    #[ORM\Column]
    #[Groups(['modification_request:read'])]
    private ?int $proposedNumberOfNights = null;

    #[ORM\Column]
    #[Groups(['modification_request:read'])]
    private ?int $proposedNightsTotal = null;

    #[ORM\Column]
    #[Groups(['modification_request:read'])]
    private ?int $proposedCleaningFee = null;

    #[ORM\Column]
    #[Groups(['modification_request:read'])]
    private ?int $proposedTouristTaxTotal = null;

    #[ORM\Column]
    #[Groups(['modification_request:read'])]
    private ?int $proposedDepositAmount = null;

    #[ORM\Column]
    #[Groups(['modification_request:read'])]
    private ?int $proposedTotalPrice = null;

    #[ORM\Column(length: 20, enumType: ModificationRequestStatus::class)]
    #[Groups(['modification_request:read'])]
    private ?ModificationRequestStatus $status = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['modification_request:read'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['modification_request:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['modification_request:read'])]
    private ?\DateTimeImmutable $respondedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): static
    {
        $this->requestedBy = $requestedBy;

        return $this;
    }

    public function getProposedCheckin(): ?\DateTimeImmutable
    {
        return $this->proposedCheckin;
    }

    public function setProposedCheckin(\DateTimeImmutable $proposedCheckin): static
    {
        $this->proposedCheckin = $proposedCheckin;

        return $this;
    }

    public function getProposedCheckout(): ?\DateTimeImmutable
    {
        return $this->proposedCheckout;
    }

    public function setProposedCheckout(\DateTimeImmutable $proposedCheckout): static
    {
        $this->proposedCheckout = $proposedCheckout;

        return $this;
    }

    public function getProposedNumberOfNights(): ?int
    {
        return $this->proposedNumberOfNights;
    }

    public function setProposedNumberOfNights(int $proposedNumberOfNights): static
    {
        $this->proposedNumberOfNights = $proposedNumberOfNights;

        return $this;
    }

    public function getProposedNightsTotal(): ?int
    {
        return $this->proposedNightsTotal;
    }

    public function setProposedNightsTotal(int $proposedNightsTotal): static
    {
        $this->proposedNightsTotal = $proposedNightsTotal;

        return $this;
    }

    public function getProposedCleaningFee(): ?int
    {
        return $this->proposedCleaningFee;
    }

    public function setProposedCleaningFee(int $proposedCleaningFee): static
    {
        $this->proposedCleaningFee = $proposedCleaningFee;

        return $this;
    }

    public function getProposedTouristTaxTotal(): ?int
    {
        return $this->proposedTouristTaxTotal;
    }

    public function setProposedTouristTaxTotal(int $proposedTouristTaxTotal): static
    {
        $this->proposedTouristTaxTotal = $proposedTouristTaxTotal;

        return $this;
    }

    public function getProposedDepositAmount(): ?int
    {
        return $this->proposedDepositAmount;
    }

    public function setProposedDepositAmount(int $proposedDepositAmount): static
    {
        $this->proposedDepositAmount = $proposedDepositAmount;

        return $this;
    }

    public function getProposedTotalPrice(): ?int
    {
        return $this->proposedTotalPrice;
    }

    public function setProposedTotalPrice(int $proposedTotalPrice): static
    {
        $this->proposedTotalPrice = $proposedTotalPrice;

        return $this;
    }

    public function getStatus(): ?ModificationRequestStatus
    {
        return $this->status;
    }

    public function setStatus(ModificationRequestStatus $status): static
    {
        $this->status = $status;

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

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): static
    {
        $this->respondedAt = $respondedAt;

        return $this;
    }
}
