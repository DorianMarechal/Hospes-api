<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ModifyBookingDatesRequest
{
    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $checkin = null;

    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $checkout = null;
}
