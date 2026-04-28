<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\State\AvailabilitySearchProvider;

#[ApiResource(
    shortName: 'AvailabilitySearch',
    operations: [
        new GetCollection(
            uriTemplate: '/availability',
            output: AvailabilitySearchResult::class,
            provider: AvailabilitySearchProvider::class,
            openapi: new Operation(
                summary: 'Search available lodgings by dates, type, capacity',
                parameters: [
                    new Parameter(name: 'checkin', in: 'query', required: true, schema: ['type' => 'string', 'format' => 'date']),
                    new Parameter(name: 'checkout', in: 'query', required: true, schema: ['type' => 'string', 'format' => 'date']),
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'capacity', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'city', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
        ),
    ],
)]
class AvailabilitySearch
{
}
