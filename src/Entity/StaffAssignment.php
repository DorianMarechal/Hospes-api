<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Dto\InviteStaffRequest;
use App\Dto\UpdateStaffLodgingsRequest;
use App\Dto\UpdateStaffPermissionsRequest;
use App\Repository\StaffAssignmentRepository;
use App\State\MyStaffProvider;
use App\State\StaffInviteProcessor;
use App\State\StaffLodgingsProcessor;
use App\State\StaffPermissionsProcessor;
use App\State\StaffRevokeProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StaffAssignmentRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/me/staff',
            security: "is_granted('ROLE_HOST')",
            provider: MyStaffProvider::class,
            normalizationContext: ['groups' => ['staff:read']],
        ),
        new Post(
            uriTemplate: '/me/staff',
            input: InviteStaffRequest::class,
            security: "is_granted('ROLE_HOST')",
            processor: StaffInviteProcessor::class,
            normalizationContext: ['groups' => ['staff:read']],
        ),
        new Put(
            uriTemplate: '/staff-assignments/{id}/permissions',
            input: UpdateStaffPermissionsRequest::class,
            security: "is_granted('STAFF_MANAGE', object)",
            processor: StaffPermissionsProcessor::class,
            normalizationContext: ['groups' => ['staff:read']],
        ),
        new Put(
            uriTemplate: '/staff-assignments/{id}/lodgings',
            input: UpdateStaffLodgingsRequest::class,
            security: "is_granted('STAFF_MANAGE', object)",
            processor: StaffLodgingsProcessor::class,
            normalizationContext: ['groups' => ['staff:read']],
        ),
        new Post(
            uriTemplate: '/staff-assignments/{id}/revoke',
            input: false,
            security: "is_granted('STAFF_MANAGE', object)",
            processor: StaffRevokeProcessor::class,
            normalizationContext: ['groups' => ['staff:read']],
        ),
    ],
    normalizationContext: ['groups' => ['staff:read']],
)]
class StaffAssignment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['staff:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['staff:read'])]
    private ?User $staff = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $host = null;

    #[ORM\Column]
    #[Groups(['staff:read'])]
    private bool $isRevoked = false;

    #[ORM\Column(length: 100, unique: true, nullable: true)]
    private ?string $invitationToken = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['staff:read'])]
    private ?string $invitationEmail = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $invitationExpiresAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['staff:read'])]
    private ?\DateTimeImmutable $invitationAcceptedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['staff:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, StaffPermission>
     */
    #[ORM\OneToMany(targetEntity: StaffPermission::class, mappedBy: 'staffAssignment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['staff:read'])]
    private Collection $permissions;

    /**
     * @var Collection<int, StaffLodging>
     */
    #[ORM\OneToMany(targetEntity: StaffLodging::class, mappedBy: 'staffAssignment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['staff:read'])]
    private Collection $lodgings;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->lodgings = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getStaff(): ?User
    {
        return $this->staff;
    }

    public function setStaff(?User $staff): static
    {
        $this->staff = $staff;

        return $this;
    }

    public function getHost(): ?User
    {
        return $this->host;
    }

    public function setHost(?User $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function isRevoked(): bool
    {
        return $this->isRevoked;
    }

    public function setIsRevoked(bool $isRevoked): static
    {
        $this->isRevoked = $isRevoked;

        return $this;
    }

    public function getInvitationToken(): ?string
    {
        return $this->invitationToken;
    }

    public function setInvitationToken(?string $invitationToken): static
    {
        $this->invitationToken = $invitationToken;

        return $this;
    }

    public function getInvitationEmail(): ?string
    {
        return $this->invitationEmail;
    }

    public function setInvitationEmail(?string $invitationEmail): static
    {
        $this->invitationEmail = $invitationEmail;

        return $this;
    }

    public function getInvitationExpiresAt(): ?\DateTimeImmutable
    {
        return $this->invitationExpiresAt;
    }

    public function setInvitationExpiresAt(?\DateTimeImmutable $invitationExpiresAt): static
    {
        $this->invitationExpiresAt = $invitationExpiresAt;

        return $this;
    }

    public function getInvitationAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->invitationAcceptedAt;
    }

    public function setInvitationAcceptedAt(?\DateTimeImmutable $invitationAcceptedAt): static
    {
        $this->invitationAcceptedAt = $invitationAcceptedAt;

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
     * @return Collection<int, StaffPermission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(StaffPermission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $permission->setStaffAssignment($this);
        }

        return $this;
    }

    public function clearPermissions(): void
    {
        $this->permissions->clear();
    }

    /**
     * @return Collection<int, StaffLodging>
     */
    public function getLodgings(): Collection
    {
        return $this->lodgings;
    }

    public function addLodging(StaffLodging $lodging): static
    {
        if (!$this->lodgings->contains($lodging)) {
            $this->lodgings->add($lodging);
            $lodging->setStaffAssignment($this);
        }

        return $this;
    }

    public function clearLodgings(): void
    {
        $this->lodgings->clear();
    }

    /**
     * @return list<string>
     */
    public function getPermissionNames(): array
    {
        return $this->permissions->map(fn (StaffPermission $p) => $p->getPermission()->value)->toArray();
    }

    public function hasPermission(\App\Enum\StaffPermission $permission): bool
    {
        foreach ($this->permissions as $p) {
            if ($p->getPermission() === $permission) {
                return true;
            }
        }

        return false;
    }

    public function hasLodgingInScope(Lodging $lodging): bool
    {
        foreach ($this->lodgings as $sl) {
            if ($sl->getLodging()?->getId()?->equals($lodging->getId())) {
                return true;
            }
        }

        return false;
    }
}
