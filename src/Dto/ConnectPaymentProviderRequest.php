<?php

namespace App\Dto;

use App\Enum\PaymentProvider;
use Symfony\Component\Validator\Constraints as Assert;

class ConnectPaymentProviderRequest
{
    #[Assert\NotNull]
    public ?PaymentProvider $provider = null;
}
