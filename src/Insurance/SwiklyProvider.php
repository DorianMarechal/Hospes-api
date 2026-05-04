<?php

namespace App\Insurance;

use Psr\Log\LoggerInterface;

class SwiklyProvider implements InsuranceProviderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function createPolicy(string $bookingReference, int $amountCents, string $currency): array
    {
        $this->logger->info('Swikly createPolicy - not implemented (requires Swikly API access)');

        return ['policy_id' => '', 'status' => 'pending'];
    }

    public function cancelPolicy(string $policyId): void
    {
        $this->logger->info('Swikly cancelPolicy - not implemented');
    }

    public function fileClaim(string $policyId, string $reason, int $amountCents): array
    {
        $this->logger->info('Swikly fileClaim - not implemented');

        return ['claim_id' => '', 'status' => 'pending'];
    }
}
