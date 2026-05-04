<?php

namespace App\Enum;

enum TaskType: string
{
    case CLEANING = 'cleaning';
    case MAINTENANCE = 'maintenance';
    case INSPECTION = 'inspection';
    case KEY_HANDOVER = 'key_handover';
}
