<?php

namespace App\Entity;

use App\Repository\HostProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HostProfileRepository::class)]
class HostProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'hostProfile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 200)]
    private ?string $businessName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $legalForm = null;

    #[ORM\Column(length: 2)]
    private ?string $country = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vatNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 100)]
    private ?string $billingCity = null;

    #[ORM\Column(length: 10)]
    private ?string $billingPostalCode = null;

    #[ORM\Column(length: 2)]
    private ?string $billingCountry = null;

    #[ORM\Column(length: 50)]
    private ?string $timezone = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, HostLegalIdentifier>
     */
    #[ORM\OneToMany(targetEntity: HostLegalIdentifier::class, mappedBy: 'hostProfileId', orphanRemoval: true)]
    private Collection $hostLegalIdentifiers;

    public function __construct()
    {
        $this->hostLegalIdentifiers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(string $businessName): static
    {
        $this->businessName = $businessName;

        return $this;
    }

    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    public function setLegalForm(?string $legalForm): static
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(string $billingCity): static
    {
        $this->billingCity = $billingCity;

        return $this;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billingPostalCode;
    }

    public function setBillingPostalCode(string $billingPostalCode): static
    {
        $this->billingPostalCode = $billingPostalCode;

        return $this;
    }

    public function getBillingCountry(): ?string
    {
        return $this->billingCountry;
    }

    public function setBillingCountry(string $billingCountry): static
    {
        $this->billingCountry = $billingCountry;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

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
     * @return Collection<int, HostLegalIdentifier>
     */
    public function getHostLegalIdentifiers(): Collection
    {
        return $this->hostLegalIdentifiers;
    }

    public function addHostLegalIdentifier(HostLegalIdentifier $hostLegalIdentifier): static
    {
        if (!$this->hostLegalIdentifiers->contains($hostLegalIdentifier)) {
            $this->hostLegalIdentifiers->add($hostLegalIdentifier);
            $hostLegalIdentifier->setHostProfile($this);
        }

        return $this;
    }

    public function removeHostLegalIdentifier(HostLegalIdentifier $hostLegalIdentifier): static
    {
        if ($this->hostLegalIdentifiers->removeElement($hostLegalIdentifier)) {
            // set the owning side to null (unless already changed)
            if ($hostLegalIdentifier->getHostProfile() === $this) {
                $hostLegalIdentifier->setHostProfile(null);
            }
        }

        return $this;
    }
}
