<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\MessageChannel;
use App\Enum\MessageTemplateTrigger;
use App\Repository\MessageTemplateRepository;
use App\State\MessageTemplateProcessor;
use App\State\MyMessageTemplatesProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageTemplateRepository::class)]
#[ORM\Index(columns: ['host_profile_id', 'trigger_type'], name: 'idx_message_template_host_trigger')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/me/message-templates',
            security: "is_granted('ROLE_HOST')",
            provider: MyMessageTemplatesProvider::class,
        ),
        new Post(
            uriTemplate: '/me/message-templates',
            security: "is_granted('ROLE_HOST')",
            processor: MessageTemplateProcessor::class,
            read: false,
        ),
        new Patch(
            uriTemplate: '/message-templates/{id}',
            security: "is_granted('ROLE_HOST')",
            processor: MessageTemplateProcessor::class,
        ),
        new Delete(
            uriTemplate: '/message-templates/{id}',
            security: "is_granted('ROLE_HOST')",
        ),
    ],
    normalizationContext: ['groups' => ['message_template:read']],
    denormalizationContext: ['groups' => ['message_template:write']],
)]
class MessageTemplate
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['message_template:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?HostProfile $hostProfile = null;

    #[ORM\Column(length: 100)]
    #[Groups(['message_template:read', 'message_template:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 30, enumType: MessageTemplateTrigger::class)]
    #[Groups(['message_template:read', 'message_template:write'])]
    #[Assert\NotNull]
    private ?MessageTemplateTrigger $triggerType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['message_template:read', 'message_template:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['message_template:read', 'message_template:write'])]
    #[Assert\NotBlank]
    private ?string $body = null;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['message_template:read', 'message_template:write'])]
    #[Assert\NotNull]
    private array $channels = [MessageChannel::EMAIL->value];

    #[ORM\Column]
    #[Groups(['message_template:read', 'message_template:write'])]
    #[Assert\PositiveOrZero]
    private int $delayMinutes = 0;

    #[ORM\Column]
    #[Groups(['message_template:read', 'message_template:write'])]
    private bool $enabled = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['message_template:read', 'message_template:write'])]
    private ?Lodging $lodging = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['message_template:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['message_template:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTriggerType(): ?MessageTemplateTrigger
    {
        return $this->triggerType;
    }

    public function setTriggerType(MessageTemplateTrigger $triggerType): static
    {
        $this->triggerType = $triggerType;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * @param string[] $channels
     */
    public function setChannels(array $channels): static
    {
        $this->channels = $channels;

        return $this;
    }

    public function getDelayMinutes(): int
    {
        return $this->delayMinutes;
    }

    public function setDelayMinutes(int $delayMinutes): static
    {
        $this->delayMinutes = $delayMinutes;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
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
}
