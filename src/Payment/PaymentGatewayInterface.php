<?php

namespace App\Payment;

use App\Entity\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create a payment intent/order and return the provider transaction ID + client secret/approval URL.
     *
     * @return array{transactionId: string, clientSecret: ?string, approvalUrl: ?string}
     */
    public function createPayment(int $amountCents, string $currency, string $connectedAccountId, string $description): array;

    /**
     * Refund a payment via the provider.
     */
    public function refund(string $providerTransactionId, int $amountCents, string $connectedAccountId): string;

    /**
     * Verify a webhook signature and return the parsed event payload.
     *
     * @return array{type: string, transactionId: string}
     */
    public function verifyWebhook(string $payload, string $signature): array;

    /**
     * Build the OAuth onboarding URL for a host.
     */
    public function buildOnboardingUrl(string $hostProfileId): string;

    /**
     * Complete the OAuth flow and return the connected account ID.
     */
    public function completeOnboarding(string $authorizationCode): string;

    /**
     * Build a dashboard/login link for the connected account.
     */
    public function buildDashboardUrl(string $connectedAccountId): string;
}
