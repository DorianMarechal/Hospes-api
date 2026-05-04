<?php

namespace App\Dto;

use App\Enum\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

class CreatePaymentRequest
{
    #[Assert\NotNull]
    public ?PaymentMethod $method = null;
}
