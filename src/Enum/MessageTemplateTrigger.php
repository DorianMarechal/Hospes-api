<?php

namespace App\Enum;

enum MessageTemplateTrigger: string
{
    case BOOKING_CREATED = 'booking_created';
    case BOOKING_CONFIRMED = 'booking_confirmed';
    case CHECKIN_MINUS_1D = 'checkin_minus_1d';
    case CHECKIN_MINUS_3H = 'checkin_minus_3h';
    case CHECKOUT_PLUS_1D = 'checkout_plus_1d';
    case REVIEW_RECEIVED = 'review_received';
}
