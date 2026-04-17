<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/auth/me/host-profile',
            processor: 'App\State\HostProfileProcessor',
            normalizationContext: ['groups' => ['host-profile:read']],
            security: "is_granted('ROLE_HOST')",
        ),
    ]
)]
class HostProfileRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        public string $businessName = '',

        #[Assert\Length(max: 50)]
        public ?string $legalForm = null,

        #[Assert\NotBlank]
        #[Assert\Country]
        public string $country = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $billingAddress = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $billingCity = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 10)]
        public string $billingPostalCode = '',

        #[Assert\NotBlank]
        #[Assert\Country]
        public string $billingCountry = '',

        #[Assert\NotBlank]
        #[Assert\Timezone]
        public string $timezone = '',
    ) {
    }
}
