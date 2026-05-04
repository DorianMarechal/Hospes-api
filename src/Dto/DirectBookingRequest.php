<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class DirectBookingRequest
{
    #[Assert\NotNull]
    public ?string $lodgingId = null;

    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $checkin = null;

    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $checkout = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $guestsCount = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $guestEmail = null;

    #[Assert\NotBlank]
    public ?string $guestFirstName = null;

    #[Assert\NotBlank]
    public ?string $guestLastName = null;

    public ?string $guestPhone = null;

    public ?string $promotionCode = null;

    public ?string $locale = 'fr';
}
