<?php

namespace App\Entity;

use App\Enum\BookingStatus;
use App\Repository\BookingStatusHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookingStatusHistoryRepository::class)]
#[ORM\Index(columns: ['booking_id'], name: 'idx_booking_status_history_booking')]
class BookingStatusHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['booking_history:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Booking $booking = null;

    #[ORM\Column(enumType: BookingStatus::class, nullable: true)]
    #[Groups(['booking_history:read'])]
    private ?BookingStatus $previousStatus = null;

    #[ORM\Column(enumType: BookingStatus::class)]
    #[Groups(['booking_history:read'])]
    private ?BookingStatus $newStatus = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['booking_history:read'])]
    private ?User $changedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['booking_history:read'])]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['booking_history:read'])]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getPreviousStatus(): ?BookingStatus
    {
        return $this->previousStatus;
    }

    public function setPreviousStatus(?BookingStatus $previousStatus): static
    {
        $this->previousStatus = $previousStatus;

        return $this;
    }

    public function getNewStatus(): ?BookingStatus
    {
        return $this->newStatus;
    }

    public function setNewStatus(BookingStatus $newStatus): static
    {
        $this->newStatus = $newStatus;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): static
    {
        $this->changedBy = $changedBy;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
