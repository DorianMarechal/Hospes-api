<?php

namespace App\Entity;

use App\Repository\WebhookEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WebhookEventRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_webhook_provider_event', columns: ['provider', 'provider_event_id'])]
class WebhookEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 20)]
    private ?string $provider = null;

    #[ORM\Column(length: 255)]
    private ?string $providerEventId = null;

    #[ORM\Column(length: 50)]
    private ?string $eventType = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $processedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProviderEventId(): ?string
    {
        return $this->providerEventId;
    }

    public function setProviderEventId(string $providerEventId): static
    {
        $this->providerEventId = $providerEventId;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;

        return $this;
    }
}
