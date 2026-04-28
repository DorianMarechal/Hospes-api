<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/auth/reset-password',
            processor: 'App\State\ResetPasswordProcessor',
            output: false,
        ),
    ],
)]
class ResetPasswordRequest
{
    #[Assert\NotBlank]
    public string $token = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 12)]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
        message: 'Password must contain at least one uppercase letter, one lowercase letter, one digit and one special character'
    )]
    #[Assert\PasswordStrength(
        minScore: Assert\PasswordStrength::STRENGTH_MEDIUM,
        message: 'Password is too weak'
    )]
    public string $newPassword = '';
}
