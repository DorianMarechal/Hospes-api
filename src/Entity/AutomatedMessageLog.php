<?php

namespace App\Entity;

use App\Enum\MessageChannel;
use App\Enum\MessageTemplateTrigger;
use App\Repository\AutomatedMessageLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AutomatedMessageLogRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_AUTO_MSG_TEMPLATE_BOOKING_CHANNEL', fields: ['messageTemplate', 'booking', 'channel'])]
#[ORM\Index(columns: ['booking_id', 'trigger_type'], name: 'idx_auto_msg_log_booking_trigger')]
class AutomatedMessageLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?MessageTemplate $messageTemplate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    #[ORM\Column(length: 30, enumType: MessageTemplateTrigger::class)]
    private ?MessageTemplateTrigger $triggerType = null;

    #[ORM\Column(length: 10, enumType: MessageChannel::class)]
    private ?MessageChannel $channel = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $sentAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getMessageTemplate(): ?MessageTemplate
    {
        return $this->messageTemplate;
    }

    public function setMessageTemplate(?MessageTemplate $messageTemplate): static
    {
        $this->messageTemplate = $messageTemplate;

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

    public function getTriggerType(): ?MessageTemplateTrigger
    {
        return $this->triggerType;
    }

    public function setTriggerType(MessageTemplateTrigger $triggerType): static
    {
        $this->triggerType = $triggerType;

        return $this;
    }

    public function getChannel(): ?MessageChannel
    {
        return $this->channel;
    }

    public function setChannel(MessageChannel $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}
