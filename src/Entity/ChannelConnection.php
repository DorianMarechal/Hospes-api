<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Enum\Channel;
use App\Repository\ChannelConnectionRepository;
use App\State\ChannelConnectionProcessor;
use App\State\ChannelDisconnectProcessor;
use App\State\LodgingChannelsProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChannelConnectionRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CHANNEL_LODGING', fields: ['lodging', 'channel'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/channels',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            provider: LodgingChannelsProvider::class,
        ),
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/channels',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            processor: ChannelConnectionProcessor::class,
            read: false,
        ),
        new Delete(
            uriTemplate: '/channels/{id}',
            security: "is_granted('ROLE_HOST')",
            processor: ChannelDisconnectProcessor::class,
        ),
        new Post(
            uriTemplate: '/channels/{id}/sync',
            security: "is_granted('ROLE_HOST')",
            input: false,
            processor: \App\State\ChannelSyncProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['channel:read']],
    denormalizationContext: ['groups' => ['channel:write']],
)]
class ChannelConnection
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['channel:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lodging $lodging = null;

    #[ORM\Column(length: 20, enumType: Channel::class)]
    #[Groups(['channel:read', 'channel:write'])]
    #[Assert\NotNull]
    private ?Channel $channel = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['channel:read', 'channel:write'])]
    private ?string $externalListingId = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['channel:write'])]
    private array $credentials = [];

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['channel:read'])]
    private ?\DateTimeImmutable $lastSyncAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['channel:read'])]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(Channel $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getExternalListingId(): ?string
    {
        return $this->externalListingId;
    }

    public function setExternalListingId(?string $externalListingId): static
    {
        $this->externalListingId = $externalListingId;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * @param array<string, mixed> $credentials
     */
    public function setCredentials(array $credentials): static
    {
        $this->credentials = $credentials;

        return $this;
    }

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeImmutable $lastSyncAt): static
    {
        $this->lastSyncAt = $lastSyncAt;

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
