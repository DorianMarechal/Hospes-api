<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Enum\IcalDirection;
use App\Repository\IcalFeedRepository;
use App\State\IcalFeedCollectionProvider;
use App\State\IcalFeedProcessor;
use App\State\IcalSyncProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IcalFeedRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/ical-feeds',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            provider: IcalFeedCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/ical-feeds',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            processor: IcalFeedProcessor::class,
        ),
        new Delete(
            security: "is_granted('LODGING_EDIT', object.getLodging())",
        ),
        new Post(
            uriTemplate: '/ical-feeds/{id}/sync',
            input: false,
            security: "is_granted('ROLE_HOST')",
            processor: IcalSyncProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['ical:read']],
    denormalizationContext: ['groups' => ['ical:write']],
)]
class IcalFeed
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['ical:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Lodging $lodging = null;

    #[ORM\Column(length: 500)]
    #[Groups(['ical:read', 'ical:write'])]
    #[Assert\NotBlank]
    #[Assert\Url]
    private ?string $url = null;

    #[ORM\Column(length: 10, enumType: IcalDirection::class)]
    #[Groups(['ical:read', 'ical:write'])]
    #[Assert\NotNull]
    private ?IcalDirection $direction = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['ical:read'])]
    private ?\DateTimeImmutable $lastSyncAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['ical:read'])]
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getDirection(): ?IcalDirection
    {
        return $this->direction;
    }

    public function setDirection(IcalDirection $direction): static
    {
        $this->direction = $direction;

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
