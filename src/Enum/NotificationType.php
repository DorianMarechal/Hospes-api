<?php

namespace App\Enum;

enum NotificationType: string
{
    case BOOKING_CONFIRMED = 'booking_confirmed';
    case BOOKING_CANCELLED = 'booking_cancelled';
    case BOOKING_MODIFIED = 'booking_modified';
    case BOOKING_EXPIRED = 'booking_expired';
    case STAFF_INVITED = 'staff_invited';
    case REVIEW_RECEIVED = 'review_received';
    case MESSAGE_RECEIVED = 'message_received';
    case PAYMENT_RECEIVED = 'payment_received';
    case MODIFICATION_REQUESTED = 'modification_requested';
    case MODIFICATION_ACCEPTED = 'modification_accepted';
    case MODIFICATION_REJECTED = 'modification_rejected';
    case MODIFICATION_EXPIRED = 'modification_expired';
    case DEPOSIT_RELEASED = 'deposit_released';
}
