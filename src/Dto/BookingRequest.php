<?php

namespace App\Dto;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class BookingRequest
{
    #[Assert\NotNull]
    public ?Uuid $lodgingId = null;

    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $checkin = null;

    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $checkout = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $guestsCount = null;
}
