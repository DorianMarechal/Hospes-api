<?php

namespace App\Enum;

enum ChannelBookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}
