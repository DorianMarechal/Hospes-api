<?php

namespace App\Enum;

enum CancellationPolicy: string
{
    case FLEXIBLE = 'flexible';
    case MODERATE = 'moderate';
    case STRICT = 'strict';
}
