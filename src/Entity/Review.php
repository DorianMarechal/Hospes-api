<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\ReviewResponseRequest;
use App\Repository\ReviewRepository;
use App\State\LodgingReviewsProvider;
use App\State\MyReviewsProvider;
use App\State\ReviewProcessor;
use App\State\ReviewResponseProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/bookings/{bookingId}/review',
            security: "is_granted('ROLE_CUSTOMER')",
            processor: ReviewProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/reviews',
            provider: LodgingReviewsProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/me/reviews',
            security: "is_granted('ROLE_CUSTOMER')",
            provider: MyReviewsProvider::class,
        ),
        new Post(
            uriTemplate: '/reviews/{id}/response',
            security: "is_granted('ROLE_HOST')",
            input: ReviewResponseRequest::class,
            processor: ReviewResponseProcessor::class,
        ),
        new Delete(
            uriTemplate: '/reviews/{id}',
            security: "is_granted('ROLE_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['review:read']],
    denormalizationContext: ['groups' => ['review:write']],
)]
class Review
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['review:read'])]
    private ?Uuid $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read'])]
    private ?Booking $booking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read'])]
    private ?Lodging $lodging = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read'])]
    private ?User $customer = null;

    #[ORM\Column]
    #[Groups(['review:read', 'review:write'])]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['review:read', 'review:write'])]
    #[Assert\NotBlank]
    private ?string $comment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['review:read'])]
    private ?string $hostResponse = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['review:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['review:read'])]
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

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getHostResponse(): ?string
    {
        return $this->hostResponse;
    }

    public function setHostResponse(?string $hostResponse): static
    {
        $this->hostResponse = $hostResponse;

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
