<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\State\AvailabilityCheckProvider;

#[ApiResource(
    shortName: 'Availability',
    operations: [
        new Get(
            uriTemplate: '/lodgings/{lodgingId}/availability',
            uriVariables: ['lodgingId' => new Link(fromClass: \App\Entity\Lodging::class)],
            output: AvailabilityResult::class,
            provider: AvailabilityCheckProvider::class,
            openapi: new Operation(
                summary: 'Check lodging availability for given dates',
                parameters: [
                    new Parameter(name: 'checkin', in: 'query', required: true, schema: ['type' => 'string', 'format' => 'date']),
                    new Parameter(name: 'checkout', in: 'query', required: true, schema: ['type' => 'string', 'format' => 'date']),
                ],
            ),
        ),
    ],
)]
class AvailabilityCheck
{
}
