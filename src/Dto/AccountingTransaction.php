<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\AccountingTransactionsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/me/accounting/transactions',
            security: "is_granted('ROLE_HOST')",
            provider: AccountingTransactionsProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['accounting:read']],
)]
class AccountingTransaction
{
    public function __construct(
        #[Groups(['accounting:read'])]
        public readonly string $id,
        #[Groups(['accounting:read'])]
        public readonly string $date,
        #[Groups(['accounting:read'])]
        public readonly string $type,
        #[Groups(['accounting:read'])]
        public readonly string $description,
        #[Groups(['accounting:read'])]
        public readonly int $amount,
        #[Groups(['accounting:read'])]
        public readonly string $currency,
        #[Groups(['accounting:read'])]
        public readonly ?string $reference,
        #[Groups(['accounting:read'])]
        public readonly ?string $lodgingName,
        #[Groups(['accounting:read'])]
        public readonly string $accountCode,
        #[Groups(['accounting:read'])]
        public readonly ?string $vatRate,
    ) {
    }
}
