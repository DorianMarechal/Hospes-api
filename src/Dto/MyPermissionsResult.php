<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\MyPermissionsProvider;

#[ApiResource(
    shortName: 'MyPermissions',
    operations: [
        new Get(
            uriTemplate: '/me/permissions',
            security: "is_granted('ROLE_STAFF')",
            provider: MyPermissionsProvider::class,
        ),
    ],
)]
class MyPermissionsResult
{
    /**
     * @param string[]                    $permissions
     * @param array<array<string,string>> $lodgings
     */
    public function __construct(
        public readonly array $permissions = [],
        public readonly array $lodgings = [],
        public readonly bool $isRevoked = false,
    ) {
    }
}
