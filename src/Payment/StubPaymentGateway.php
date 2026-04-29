<?php

namespace App\Payment;

class StubPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(int $amountCents, string $currency, string $connectedAccountId, string $description): array
    {
        return [
            'transactionId' => 'stub_txn_'.bin2hex(random_bytes(8)),
            'clientSecret' => 'stub_secret_'.bin2hex(random_bytes(8)),
            'approvalUrl' => null,
        ];
    }

    public function refund(string $providerTransactionId, int $amountCents, string $connectedAccountId): string
    {
        return 'stub_refund_'.bin2hex(random_bytes(8));
    }

    public function verifyWebhook(string $payload, string $signature): array
    {
        $event = json_decode($payload, true);

        return [
            'type' => $event['type'] ?? 'payment.succeeded',
            'transactionId' => $event['transactionId'] ?? 'stub_txn',
        ];
    }

    public function buildOnboardingUrl(string $hostProfileId): string
    {
        return 'https://stub.example.com/onboard?state='.$hostProfileId;
    }

    public function completeOnboarding(string $authorizationCode): string
    {
        return 'stub_account_'.bin2hex(random_bytes(4));
    }

    public function buildDashboardUrl(string $connectedAccountId): string
    {
        return 'https://stub.example.com/dashboard';
    }
}
