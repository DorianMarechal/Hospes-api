<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Validator\ValidPhoneNumber as PhoneValidation;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/auth/register',
            processor: 'App\State\RegisterProcessor',
            normalizationContext: ['groups' => ['user:read']],
        )
    ]
)]
class RegisterRequest{

    public function __construct(

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = "",

        #[Assert\NotBlank]
        #[Assert\Length(min: 12)]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
            message: 'Password must contain at least one uppercase letter, one lowercase letter, one digit and one special character'
        )]
        #[Assert\PasswordStrength(
            minScore: Assert\PasswordStrength::STRENGTH_MEDIUM,
            message: 'Password is too weak. Avoid repeated characters, common words and predictable patterns'
        )]
        public string $password = "",

        #[Assert\NotBlank]
        public string $firstName = "",

        #[Assert\NotBlank]
        public string $lastName = "",

        #[PhoneValidation]
        public ?string $phone = null,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['host', 'customer'])]
        public string $accountType = "",

    ){}

}

