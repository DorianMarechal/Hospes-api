<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoSeasonOverlap extends Constraint
{
    public string $message = 'This season overlaps with an existing season "{{ name }}" ({{ start }} - {{ end }}).';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
