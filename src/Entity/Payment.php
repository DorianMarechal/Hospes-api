<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\CreatePaymentRequest;
use App\Dto\RefundPaymentRequest;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Repository\PaymentRepository;
use App\State\BookingPaymentsProvider;
use App\State\MyPaymentsProvider;
use App\State\PaymentCreateProcessor;
use App\State\PaymentRefundProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Index(columns: ['booking_id'], name: 'idx_payment_booking')]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/bookings/{bookingId}/payments',
            input: CreatePaymentRequest::class,
            security: "is_granted('ROLE_CUSTOMER')",
            processor: PaymentCreateProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/bookings/{bookingId}/payments',
            security: "is_granted('ROLE_USER')",
            provider: BookingPaymentsProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/me/payments',
            security: "is_granted('ROLE_HOST')",
            provider: MyPaymentsProvider::class,
        ),
        new Post(
            uriTemplate: '/payments/{id}/refund',
            input: RefundPaymentRequest::class,
            security: "is_granted('ROLE_HOST') or is_granted('ROLE_ADMIN')",
            processor: PaymentRefundProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['payment:read']],
)]
class Payment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['payment:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['payment:read'])]
    private ?Booking $booking = null;

    #[ORM\Column]
    #[Groups(['payment:read'])]
    private ?int $amount = null;

    #[ORM\Column(enumType: PaymentType::class)]
    #[Groups(['payment:read'])]
    private ?PaymentType $type = null;

    #[ORM\Column(enumType: PaymentMethod::class)]
    #[Groups(['payment:read'])]
    private ?PaymentMethod $method = null;

    #[ORM\Column(enumType: PaymentStatus::class)]
    #[Groups(['payment:read'])]
    private ?PaymentStatus $status = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['payment:read'])]
    private ?string $provider = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['payment:read'])]
    private ?string $providerTransactionId = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['payment:read'])]
    private ?string $refundReason = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['payment:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Groups(['payment:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getType(): ?PaymentType
    {
        return $this->type;
    }

    public function setType(PaymentType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMethod(): ?PaymentMethod
    {
        return $this->method;
    }

    public function setMethod(PaymentMethod $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getStatus(): ?PaymentStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProviderTransactionId(): ?string
    {
        return $this->providerTransactionId;
    }

    public function setProviderTransactionId(?string $providerTransactionId): static
    {
        $this->providerTransactionId = $providerTransactionId;

        return $this;
    }

    public function getRefundReason(): ?string
    {
        return $this->refundReason;
    }

    public function setRefundReason(?string $refundReason): static
    {
        $this->refundReason = $refundReason;

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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
