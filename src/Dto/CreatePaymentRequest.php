<?php

namespace App\Dto;

use App\Enum\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

class CreatePaymentRequest
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $amount = null;

    #[Assert\NotNull]
    public ?PaymentMethod $method = null;

    public ?string $providerTransactionId = null;
}
