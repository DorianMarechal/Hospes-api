<?php

namespace App\Entity;

use App\Repository\BookingExtraRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookingExtraRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_BOOKING_EXTRA', fields: ['booking', 'extra'])]
class BookingExtra
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['booking:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read'])]
    private ?Extra $extra = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private int $quantity = 1;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private int $unitPrice = 0;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private int $totalPrice = 0;

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

    public function getExtra(): ?Extra
    {
        return $this->extra;
    }

    public function setExtra(?Extra $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }
}
