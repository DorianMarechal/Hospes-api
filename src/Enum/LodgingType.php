<?php

namespace App\Enum;

enum LodgingType: string
{
    case HOTEL_ROOM = 'hotel_room';
    case GITE = 'gite';
    case CABIN = 'cabin';
    case BED_AND_BREAKFAST = 'bed_and_breakfast';
    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case VILLA = 'villa';
    case STUDIO = 'studio';
    case LOFT = 'loft';
    case BUNGALOW = 'bungalow';
}