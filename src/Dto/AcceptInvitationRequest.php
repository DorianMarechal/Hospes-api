<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'StaffInvitation',
    operations: [
        new Post(
            uriTemplate: '/staff-invitations/{token}/accept',
            processor: 'App\State\AcceptInvitationProcessor',
            normalizationContext: ['groups' => ['user:read']],
        ),
    ],
)]
class AcceptInvitationRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 12)]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
        message: 'Password must contain at least one uppercase letter, one lowercase letter, one digit and one special character'
    )]
    public string $password = '';

    #[Assert\NotBlank]
    public string $firstName = '';

    #[Assert\NotBlank]
    public string $lastName = '';
}
