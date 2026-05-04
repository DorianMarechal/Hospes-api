<?php

namespace App\Enum;

enum MessageTemplateTrigger: string
{
    case BOOKING_CREATED = 'booking_created';
    case BOOKING_CONFIRMED = 'booking_confirmed';
    case CHECKIN_MINUS_1D = 'checkin_minus_1d';
    case CHECKIN_SAME_DAY = 'checkin_same_day';
    case CHECKOUT_PLUS_1D = 'checkout_plus_1d';
    case REVIEW_RECEIVED = 'review_received';
}
