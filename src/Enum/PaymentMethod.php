<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case PAYPAL = 'paypal';
}
