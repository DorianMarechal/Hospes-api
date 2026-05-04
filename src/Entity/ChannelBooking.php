<?php

namespace App\Entity;

use App\Enum\Channel;
use App\Enum\ChannelBookingStatus;
use App\Repository\ChannelBookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ChannelBookingRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CHANNEL_BOOKING_EXTERNAL', fields: ['channel', 'externalReservationId'])]
#[ORM\Index(columns: ['booking_id'], name: 'idx_channel_booking_booking')]
class ChannelBooking
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    #[ORM\Column(length: 20, enumType: Channel::class)]
    private ?Channel $channel = null;

    #[ORM\Column(length: 255)]
    private ?string $externalReservationId = null;

    #[ORM\Column(length: 20, enumType: ChannelBookingStatus::class)]
    private ChannelBookingStatus $externalStatus = ChannelBookingStatus::CONFIRMED;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $importedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSyncAt = null;

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

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(Channel $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getExternalReservationId(): ?string
    {
        return $this->externalReservationId;
    }

    public function setExternalReservationId(string $externalReservationId): static
    {
        $this->externalReservationId = $externalReservationId;

        return $this;
    }

    public function getExternalStatus(): ChannelBookingStatus
    {
        return $this->externalStatus;
    }

    public function setExternalStatus(ChannelBookingStatus $externalStatus): static
    {
        $this->externalStatus = $externalStatus;

        return $this;
    }

    public function getImportedAt(): ?\DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function setImportedAt(\DateTimeImmutable $importedAt): static
    {
        $this->importedAt = $importedAt;

        return $this;
    }

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeImmutable $lastSyncAt): static
    {
        $this->lastSyncAt = $lastSyncAt;

        return $this;
    }
}
