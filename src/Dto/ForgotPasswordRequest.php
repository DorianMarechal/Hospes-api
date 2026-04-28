<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/auth/forgot-password',
            processor: 'App\State\ForgotPasswordProcessor',
            output: false,
        ),
    ],
)]
class ForgotPasswordRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';
}
