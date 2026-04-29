<?php

namespace App\Enum;

enum PaymentType: string
{
    case BOOKING = 'booking';
    case REFUND = 'refund';
}
