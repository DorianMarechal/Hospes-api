<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\AdminBookingsProvider;
use App\State\AdminDeactivateUserProcessor;
use App\State\AdminLodgingDeleteProcessor;
use App\State\AdminLodgingsProvider;
use App\State\AdminReactivateUserProcessor;
use App\State\AdminReviewDeleteProcessor;
use App\State\AdminReviewsProvider;
use App\State\AdminStatsProvider;
use App\State\AdminUserProvider;
use App\State\AdminUsersProvider;

#[ApiResource(
    shortName: 'Admin',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/users',
            security: "is_granted('ROLE_ADMIN')",
            provider: AdminUsersProvider::class,
            normalizationContext: ['groups' => ['user:read']],
        ),
        new Get(
            uriTemplate: '/admin/users/{id}',
            security: "is_granted('ROLE_ADMIN')",
            provider: AdminUserProvider::class,
            normalizationContext: ['groups' => ['user:read']],
        ),
        new Post(
            uriTemplate: '/admin/users/{id}/deactivate',
            input: false,
            security: "is_granted('ROLE_ADMIN')",
            processor: AdminDeactivateUserProcessor::class,
            normalizationContext: ['groups' => ['user:read']],
        ),
        new Post(
            uriTemplate: '/admin/users/{id}/reactivate',
            input: false,
            security: "is_granted('ROLE_ADMIN')",
            processor: AdminReactivateUserProcessor::class,
            normalizationContext: ['groups' => ['user:read']],
        ),
        new GetCollection(
            uriTemplate: '/admin/lodgings',
            security: "is_granted('ROLE_ADMIN')",
            provider: AdminLodgingsProvider::class,
            normalizationContext: ['groups' => ['lodging:read']],
        ),
        new Delete(
            uriTemplate: '/admin/lodgings/{id}',
            security: "is_granted('ROLE_ADMIN')",
            processor: AdminLodgingDeleteProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/admin/bookings',
            security: "is_granted('ROLE_ADMIN')",
            provider: AdminBookingsProvider::class,
            normalizationContext: ['groups' => ['booking:read']],
        ),
        new GetCollection(
            uriTemplate: '/admin/reviews',
            security: "is_granted('ROLE_ADMIN')",
            provider: AdminReviewsProvider::class,
            normalizationContext: ['groups' => ['review:read']],
        ),
        new Delete(
            uriTemplate: '/admin/reviews/{id}',
            security: "is_granted('ROLE_ADMIN')",
            processor: AdminReviewDeleteProcessor::class,
        ),
        new Get(
            uriTemplate: '/admin/stats',
            security: "is_granted('ROLE_ADMIN')",
            provider: AdminStatsProvider::class,
        ),
    ],
)]
class AdminDeactivateRequest
{
}
