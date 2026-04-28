<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\HostProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HostProfileRepository::class)]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['host-profile:read']],
)]
class HostProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['host-profile:read'])]
    private ?Uuid $id = null;

    #[ORM\OneToOne(inversedBy: 'hostProfile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 200)]
    #[Groups(['host-profile:read'])]
    private ?string $businessName = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['host-profile:read'])]
    private ?string $legalForm = null;

    #[ORM\Column(length: 2)]
    #[Groups(['host-profile:read'])]
    private ?string $country = null;

    #[ORM\Column(length: 255)]
    #[Groups(['host-profile:read'])]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 100)]
    #[Groups(['host-profile:read'])]
    private ?string $billingCity = null;

    #[ORM\Column(length: 10)]
    #[Groups(['host-profile:read'])]
    private ?string $billingPostalCode = null;

    #[ORM\Column(length: 2)]
    #[Groups(['host-profile:read'])]
    private ?string $billingCountry = null;

    #[ORM\Column(length: 50)]
    #[Groups(['host-profile:read'])]
    private ?string $timezone = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['host-profile:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['host-profile:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, HostLegalIdentifier>
     */
    #[ORM\OneToMany(targetEntity: HostLegalIdentifier::class, mappedBy: 'hostProfile', orphanRemoval: true)]
    private Collection $hostLegalIdentifiers;

    /**
     * @var Collection<int, Lodging>
     */
    #[ORM\OneToMany(targetEntity: Lodging::class, mappedBy: 'host')]
    private Collection $lodgings;

    public function __construct()
    {
        $this->hostLegalIdentifiers = new ArrayCollection();
        $this->lodgings = new ArrayCollection();
    }

    public function getId(): ?Uuid
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

    /**
     * @return Collection<int, Lodging>
     */
    public function getLodgings(): Collection
    {
        return $this->lodgings;
    }

    public function addLodging(Lodging $lodging): static
    {
        if (!$this->lodgings->contains($lodging)) {
            $this->lodgings->add($lodging);
            $lodging->setHost($this);
        }

        return $this;
    }

    public function removeLodging(Lodging $lodging): static
    {
        if ($this->lodgings->removeElement($lodging)) {
            // set the owning side to null (unless already changed)
            if ($lodging->getHost() === $this) {
                $lodging->setHost(null);
            }
        }

        return $this;
    }
}
