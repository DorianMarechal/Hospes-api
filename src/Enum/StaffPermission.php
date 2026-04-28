<?php

namespace App\Enum;

enum StaffPermission: string
{
    case CAN_VIEW_BOOKINGS = 'can_view_bookings';
    case CAN_EDIT_BOOKINGS = 'can_edit_bookings';
    case CAN_BLOCK_DATES = 'can_block_dates';
    case CAN_VIEW_REVENUE = 'can_view_revenue';
    case CAN_MANAGE_LODGINGS = 'can_manage_lodgings';
}
