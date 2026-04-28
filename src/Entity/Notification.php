<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\NotificationRepository;
use App\State\MyNotificationsProvider;
use App\State\NotificationReadAllProcessor;
use App\State\NotificationReadProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Index(columns: ['user_id', 'is_read', 'created_at'], name: 'idx_notification_user_read_date')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/me/notifications',
            security: "is_granted('ROLE_USER')",
            provider: MyNotificationsProvider::class,
        ),
        new Post(
            uriTemplate: '/notifications/{id}/read',
            security: "is_granted('ROLE_USER')",
            input: false,
            processor: NotificationReadProcessor::class,
        ),
        new Post(
            uriTemplate: '/me/notifications/read-all',
            security: "is_granted('ROLE_USER')",
            input: false,
            processor: NotificationReadAllProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['notification:read']],
)]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['notification:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 30)]
    #[Groups(['notification:read'])]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['notification:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['notification:read'])]
    private ?string $content = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['notification:read'])]
    private ?string $relatedEntityType = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    #[Groups(['notification:read'])]
    private ?Uuid $relatedEntityId = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['notification:read'])]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['notification:read'])]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['notification:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getRelatedEntityType(): ?string
    {
        return $this->relatedEntityType;
    }

    public function setRelatedEntityType(?string $relatedEntityType): static
    {
        $this->relatedEntityType = $relatedEntityType;

        return $this;
    }

    public function getRelatedEntityId(): ?Uuid
    {
        return $this->relatedEntityId;
    }

    public function setRelatedEntityId(?Uuid $relatedEntityId): static
    {
        $this->relatedEntityId = $relatedEntityId;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;

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
