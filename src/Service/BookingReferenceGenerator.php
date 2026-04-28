<?php

namespace App\Service;

class BookingReferenceGenerator
{
    public function generate(): string
    {
        return 'HOS-'.strtoupper(bin2hex(random_bytes(4))).'-'.date('y');
    }
}
