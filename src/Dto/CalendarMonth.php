<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\State\CalendarProvider;

#[ApiResource(
    shortName: 'Calendar',
    operations: [
        new Get(
            uriTemplate: '/lodgings/{lodgingId}/calendar',
            output: false,
            provider: CalendarProvider::class,
            security: "is_granted('ROLE_HOST')",
            openapi: new Operation(
                summary: 'Get monthly calendar with bookings and blocked dates',
                parameters: [
                    new Parameter(name: 'month', in: 'query', required: true, schema: ['type' => 'string', 'example' => '2026-05']),
                ],
            ),
        ),
    ],
)]
class CalendarMonth
{
}
