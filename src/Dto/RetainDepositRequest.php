<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RetainDepositRequest
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $retainedAmount = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    public ?string $reason = null;
}
