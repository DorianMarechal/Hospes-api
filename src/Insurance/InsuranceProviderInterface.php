<?php

namespace App\Insurance;

interface InsuranceProviderInterface
{
    /**
     * @return array{policy_id: string, status: string}
     */
    public function createPolicy(string $bookingReference, int $amountCents, string $currency): array;

    public function cancelPolicy(string $policyId): void;

    /**
     * @return array{claim_id: string, status: string}
     */
    public function fileClaim(string $policyId, string $reason, int $amountCents): array;
}
