<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use App\Repository\AccessCodeRepository;
use App\State\BookingAccessCodeProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AccessCodeRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_ACCESS_CODE_BOOKING', fields: ['booking'])]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/bookings/{bookingId}/access-code',
            uriVariables: ['bookingId' => new Link(fromClass: Booking::class)],
            security: "is_granted('ROLE_USER')",
            provider: BookingAccessCodeProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['access_code:read']],
)]
class AccessCode
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['access_code:read'])]
    private ?Uuid $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    #[ORM\Column(length: 20)]
    #[Groups(['access_code:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['access_code:read'])]
    private ?string $lockProvider = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lockId = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['access_code:read'])]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['access_code:read'])]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\Column]
    #[Groups(['access_code:read'])]
    private bool $revoked = false;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['access_code:read'])]
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLockProvider(): ?string
    {
        return $this->lockProvider;
    }

    public function setLockProvider(?string $lockProvider): static
    {
        $this->lockProvider = $lockProvider;

        return $this;
    }

    public function getLockId(): ?string
    {
        return $this->lockId;
    }

    public function setLockId(?string $lockId): static
    {
        $this->lockId = $lockId;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeImmutable $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(\DateTimeImmutable $validTo): static
    {
        $this->validTo = $validTo;

        return $this;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function setRevoked(bool $revoked): static
    {
        $this->revoked = $revoked;

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
