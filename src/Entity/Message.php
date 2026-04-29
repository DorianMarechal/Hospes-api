<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\MessageRepository;
use App\State\ConversationMessagesProvider;
use App\State\MessageProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/conversations/{conversationId}/messages',
            uriVariables: ['conversationId' => new Link(fromClass: Conversation::class, toProperty: 'conversation')],
            security: "is_granted('ROLE_USER')",
            provider: ConversationMessagesProvider::class,
        ),
        new Post(
            uriTemplate: '/conversations/{conversationId}/messages',
            uriVariables: ['conversationId' => new Link(fromClass: Conversation::class, toProperty: 'conversation')],
            security: "is_granted('ROLE_USER')",
            processor: MessageProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']],
)]
class Message
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['message:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read'])]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read'])]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['message:read', 'message:write'])]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['message:read'])]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['message:read'])]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['message:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

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
