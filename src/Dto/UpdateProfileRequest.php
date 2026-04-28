<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use App\Validator\ValidPhoneNumber as PhoneValidation;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/auth/me',
            security: "is_granted('ROLE_USER')",
            processor: 'App\State\UpdateProfileProcessor',
            normalizationContext: ['groups' => ['user:read']],
        ),
    ],
)]
class UpdateProfileRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $firstName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $lastName = '';

    #[PhoneValidation]
    public ?string $phone = null;
}
