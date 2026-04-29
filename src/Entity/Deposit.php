<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Dto\RetainDepositRequest;
use App\Enum\DepositStatus;
use App\Repository\DepositRepository;
use App\State\BookingDepositProvider;
use App\State\DepositReleaseProcessor;
use App\State\DepositRetainProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DepositRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/bookings/{bookingId}/deposit',
            uriVariables: ['bookingId' => new Link(fromClass: Booking::class, toProperty: 'booking')],
            security: "is_granted('ROLE_USER')",
            provider: BookingDepositProvider::class,
        ),
        new Post(
            uriTemplate: '/bookings/{bookingId}/deposit/retain',
            uriVariables: ['bookingId' => new Link(fromClass: Booking::class, toProperty: 'booking')],
            input: RetainDepositRequest::class,
            denormalizationContext: [],
            security: "is_granted('ROLE_HOST')",
            processor: DepositRetainProcessor::class,
        ),
        new Post(
            uriTemplate: '/bookings/{bookingId}/deposit/release',
            uriVariables: ['bookingId' => new Link(fromClass: Booking::class, toProperty: 'booking')],
            input: false,
            security: "is_granted('ROLE_HOST')",
            processor: DepositReleaseProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['deposit:read']],
)]
class Deposit
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['deposit:read', 'booking:read'])]
    private ?Uuid $id = null;

    #[ORM\OneToOne(inversedBy: 'deposit')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Groups(['deposit:read'])]
    private ?Booking $booking = null;

    #[ORM\Column]
    #[Groups(['deposit:read', 'booking:read'])]
    private ?int $amount = null;

    #[ORM\Column(enumType: DepositStatus::class)]
    #[Groups(['deposit:read', 'booking:read'])]
    private ?DepositStatus $status = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['deposit:read', 'booking:read'])]
    private int $retainedAmount = 0;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['deposit:read', 'booking:read'])]
    private ?string $retainedReason = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['deposit:read', 'booking:read'])]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['deposit:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['deposit:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStatus(): ?DepositStatus
    {
        return $this->status;
    }

    public function setStatus(DepositStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRetainedAmount(): int
    {
        return $this->retainedAmount;
    }

    public function setRetainedAmount(int $retainedAmount): static
    {
        $this->retainedAmount = $retainedAmount;

        return $this;
    }

    public function getRetainedReason(): ?string
    {
        return $this->retainedReason;
    }

    public function setRetainedReason(?string $retainedReason): static
    {
        $this->retainedReason = $retainedReason;

        return $this;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): static
    {
        $this->releasedAt = $releasedAt;

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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
