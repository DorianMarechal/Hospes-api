<?php

namespace App\Enum;

enum MessageChannel: string
{
    case EMAIL = 'email';
    case IN_APP = 'in_app';
    case SMS = 'sms';
}
