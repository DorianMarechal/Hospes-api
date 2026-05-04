<?php

namespace App\Entity;

use App\Repository\InsuranceClaimRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InsuranceClaimRepository::class)]
#[ORM\Index(columns: ['booking_id'], name: 'idx_insurance_claim_booking')]
class InsuranceClaim
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['insurance:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    #[ORM\Column(length: 30)]
    #[Groups(['insurance:read'])]
    private ?string $provider = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['insurance:read'])]
    private ?string $policyId = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['insurance:read'])]
    private ?string $claimId = null;

    #[ORM\Column(length: 20)]
    #[Groups(['insurance:read'])]
    private string $status = 'proposed';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['insurance:read'])]
    private ?string $reason = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['insurance:read'])]
    private ?int $claimAmount = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['insurance:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['insurance:read'])]
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

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getPolicyId(): ?string
    {
        return $this->policyId;
    }

    public function setPolicyId(?string $policyId): static
    {
        $this->policyId = $policyId;

        return $this;
    }

    public function getClaimId(): ?string
    {
        return $this->claimId;
    }

    public function setClaimId(?string $claimId): static
    {
        $this->claimId = $claimId;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getClaimAmount(): ?int
    {
        return $this->claimAmount;
    }

    public function setClaimAmount(?int $claimAmount): static
    {
        $this->claimAmount = $claimAmount;

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
