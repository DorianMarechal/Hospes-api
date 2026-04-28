<?php

namespace App\Enum;

enum ConversationStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';
}
