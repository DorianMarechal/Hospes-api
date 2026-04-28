<?php

namespace App\Enum;

enum IcalDirection: string
{
    case IMPORT = 'import';
    case EXPORT = 'export';
}
