<?php

namespace App\Enum;

enum DepositStatus: string
{
    case HELD = 'held';
    case RELEASED = 'released';
    case PARTIALLY_RETAINED = 'partially_retained';
    case FULLY_RETAINED = 'fully_retained';
}
