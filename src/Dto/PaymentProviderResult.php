<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Enum\PaymentProvider;
use App\State\PaymentProviderConnectProcessor;
use App\State\PaymentProviderDisconnectProcessor;
use App\State\PaymentProviderStatusProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/me/payment-provider',
            security: "is_granted('ROLE_HOST')",
            provider: PaymentProviderStatusProvider::class,
        ),
        new Post(
            uriTemplate: '/me/payment-provider/connect',
            input: ConnectPaymentProviderRequest::class,
            security: "is_granted('ROLE_HOST')",
            processor: PaymentProviderConnectProcessor::class,
        ),
        new Post(
            uriTemplate: '/me/payment-provider/disconnect',
            input: false,
            security: "is_granted('ROLE_HOST')",
            processor: PaymentProviderDisconnectProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['payment-provider:read']],
)]
class PaymentProviderResult
{
    #[Groups(['payment-provider:read'])]
    public ?PaymentProvider $provider = null;

    #[Groups(['payment-provider:read'])]
    public ?string $accountId = null;

    #[Groups(['payment-provider:read'])]
    public bool $isOnboarded = false;

    #[Groups(['payment-provider:read'])]
    public ?\DateTimeImmutable $onboardedAt = null;

    #[Groups(['payment-provider:read'])]
    public ?string $connectUrl = null;
}
