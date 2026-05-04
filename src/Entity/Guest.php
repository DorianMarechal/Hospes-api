<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Enum\IdentityDocumentType;
use App\Repository\GuestRepository;
use App\State\BookingGuestsProvider;
use App\State\GuestProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GuestRepository::class)]
#[ORM\Index(columns: ['booking_id'], name: 'idx_guest_booking')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/bookings/{bookingId}/guests',
            uriVariables: ['bookingId' => new Link(fromClass: Booking::class, toProperty: 'booking')],
            security: "is_granted('ROLE_USER')",
            provider: BookingGuestsProvider::class,
        ),
        new Post(
            uriTemplate: '/bookings/{bookingId}/guests',
            uriVariables: ['bookingId' => new Link(fromClass: Booking::class, toProperty: 'booking')],
            security: "is_granted('ROLE_USER')",
            processor: GuestProcessor::class,
            read: false,
        ),
    ],
    normalizationContext: ['groups' => ['guest:read']],
    denormalizationContext: ['groups' => ['guest:write']],
)]
class Guest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['guest:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    #[ORM\Column(length: 100)]
    #[Groups(['guest:read', 'guest:write'])]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Groups(['guest:read', 'guest:write'])]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 2, nullable: true)]
    #[Groups(['guest:read', 'guest:write'])]
    #[Assert\Length(exactly: 2)]
    private ?string $nationality = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['guest:read', 'guest:write'])]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 20, nullable: true, enumType: IdentityDocumentType::class)]
    #[Groups(['guest:read', 'guest:write'])]
    private ?IdentityDocumentType $idType = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['guest:read', 'guest:write'])]
    private ?string $idNumber = null;

    #[ORM\Column]
    #[Groups(['guest:read'])]
    private bool $gdprConsent = false;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['guest:read'])]
    private ?\DateTimeImmutable $gdprConsentAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['guest:read'])]
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getIdType(): ?IdentityDocumentType
    {
        return $this->idType;
    }

    public function setIdType(?IdentityDocumentType $idType): static
    {
        $this->idType = $idType;

        return $this;
    }

    public function getIdNumber(): ?string
    {
        return $this->idNumber;
    }

    public function setIdNumber(?string $idNumber): static
    {
        $this->idNumber = $idNumber;

        return $this;
    }

    public function isGdprConsent(): bool
    {
        return $this->gdprConsent;
    }

    public function setGdprConsent(bool $gdprConsent): static
    {
        $this->gdprConsent = $gdprConsent;
        if ($gdprConsent) {
            $this->gdprConsentAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getGdprConsentAt(): ?\DateTimeImmutable
    {
        return $this->gdprConsentAt;
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
