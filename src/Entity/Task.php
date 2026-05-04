<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\TaskStatus;
use App\Enum\TaskType;
use App\Repository\TaskRepository;
use App\State\MyTasksProvider;
use App\State\TaskCreateProcessor;
use App\State\TaskUpdateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Index(columns: ['assignee_id', 'status', 'due_date'], name: 'idx_task_assignee_status_due')]
#[ORM\Index(columns: ['lodging_id', 'due_date'], name: 'idx_task_lodging_due')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/me/tasks',
            security: "is_granted('ROLE_USER')",
            provider: MyTasksProvider::class,
        ),
        new Get(
            uriTemplate: '/tasks/{id}',
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: '/me/tasks',
            security: "is_granted('ROLE_HOST')",
            processor: TaskCreateProcessor::class,
            read: false,
        ),
        new Patch(
            uriTemplate: '/tasks/{id}',
            security: "is_granted('ROLE_USER')",
            processor: TaskUpdateProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['task:read']],
    denormalizationContext: ['groups' => ['task:write']],
)]
class Task
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['task:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['task:read'])]
    private ?Lodging $lodging = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['task:read'])]
    private ?Booking $booking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?User $assignee = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?HostProfile $hostProfile = null;

    #[ORM\Column(length: 20, enumType: TaskType::class)]
    #[Groups(['task:read', 'task:write'])]
    #[Assert\NotNull]
    private ?TaskType $type = null;

    #[ORM\Column(length: 20, enumType: TaskStatus::class)]
    #[Groups(['task:read', 'task:write'])]
    private TaskStatus $status = TaskStatus::PENDING;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['task:read', 'task:write'])]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['task:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['task:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['task:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getLodging(): ?Lodging
    {
        return $this->lodging;
    }

    public function setLodging(?Lodging $lodging): static
    {
        $this->lodging = $lodging;

        return $this;
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

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;

        return $this;
    }

    public function getHostProfile(): ?HostProfile
    {
        return $this->hostProfile;
    }

    public function setHostProfile(?HostProfile $hostProfile): static
    {
        $this->hostProfile = $hostProfile;

        return $this;
    }

    public function getType(): ?TaskType
    {
        return $this->type;
    }

    public function setType(TaskType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }
}
