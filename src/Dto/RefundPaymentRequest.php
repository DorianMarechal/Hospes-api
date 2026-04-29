<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RefundPaymentRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    public ?string $reason = null;
}
