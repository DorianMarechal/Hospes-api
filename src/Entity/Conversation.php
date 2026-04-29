<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Enum\ConversationStatus;
use App\Repository\ConversationRepository;
use App\State\ConversationProcessor;
use App\State\ConversationReadProcessor;
use App\State\MyConversationsProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CONVERSATION_LODGING_CUSTOMER', fields: ['lodging', 'customer'])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/conversations',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_CUSTOMER')",
            processor: ConversationProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/me/conversations',
            security: "is_granted('ROLE_USER')",
            provider: MyConversationsProvider::class,
        ),
        new Post(
            uriTemplate: '/conversations/{id}/read',
            security: "is_granted('ROLE_USER')",
            input: false,
            processor: ConversationReadProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['conversation:read']],
    denormalizationContext: ['groups' => ['conversation:write']],
)]
class Conversation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['conversation:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['conversation:read'])]
    private ?Lodging $lodging = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['conversation:read'])]
    private ?User $customer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['conversation:read'])]
    private ?User $host = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['conversation:read'])]
    private ?Booking $booking = null;

    #[ORM\Column(enumType: ConversationStatus::class)]
    #[Groups(['conversation:read', 'conversation:write'])]
    private ConversationStatus $status = ConversationStatus::OPEN;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['conversation:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['conversation:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

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

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

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

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getStatus(): ConversationStatus
    {
        return $this->status;
    }

    public function setStatus(ConversationStatus $status): static
    {
        $this->status = $status;

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
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }
}
