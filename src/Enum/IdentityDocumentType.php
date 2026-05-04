<?php

namespace App\Enum;

enum IdentityDocumentType: string
{
    case PASSPORT = 'passport';
    case NATIONAL_ID = 'national_id';
    case DRIVING_LICENSE = 'driving_license';
    case RESIDENCE_PERMIT = 'residence_permit';
}
