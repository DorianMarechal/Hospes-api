<?php

namespace App\Payment;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PayPalGateway implements PaymentGatewayInterface
{
    private string $paypalClientId;
    private string $paypalClientSecret;
    private string $paypalWebhookId;
    private string $appBaseUrl;
    private bool $sandbox;

    public function __construct(
        string $paypalClientId,
        string $paypalClientSecret,
        string $paypalWebhookId,
        string $appBaseUrl,
        bool $sandbox,
        private HttpClientInterface $httpClient,
    ) {
        $this->paypalClientId = $paypalClientId;
        $this->paypalClientSecret = $paypalClientSecret;
        $this->paypalWebhookId = $paypalWebhookId;
        $this->appBaseUrl = $appBaseUrl;
        $this->sandbox = $sandbox;
    }

    private function baseUrl(): string
    {
        return $this->sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function getAccessToken(): string
    {
        $response = $this->httpClient->request('POST', $this->baseUrl().'/v1/oauth2/token', [
            'auth_basic' => [$this->paypalClientId, $this->paypalClientSecret],
            'body' => ['grant_type' => 'client_credentials'],
        ]);

        /** @var array{access_token: string} $data */
        $data = $response->toArray();

        return $data['access_token'];
    }

    public function createPayment(int $amountCents, string $currency, string $connectedAccountId, string $description): array
    {
        $token = $this->getAccessToken();
        $amountStr = number_format($amountCents / 100, 2, '.', '');

        $response = $this->httpClient->request('POST', $this->baseUrl().'/v2/checkout/orders', [
            'auth_bearer' => $token,
            'json' => [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => ['currency_code' => strtoupper($currency), 'value' => $amountStr],
                    'description' => $description,
                    'payee' => ['merchant_id' => $connectedAccountId],
                ]],
            ],
        ]);

        /** @var array{id: string, links: list<array{rel: string, href: string}>} $order */
        $order = $response->toArray();

        $approvalUrl = null;
        foreach ($order['links'] as $link) {
            if ('approve' === $link['rel']) {
                $approvalUrl = $link['href'];
                break;
            }
        }

        return [
            'transactionId' => $order['id'],
            'clientSecret' => null,
            'approvalUrl' => $approvalUrl,
        ];
    }

    public function refund(string $providerTransactionId, int $amountCents, string $connectedAccountId): string
    {
        $token = $this->getAccessToken();
        $amountStr = number_format($amountCents / 100, 2, '.', '');

        $response = $this->httpClient->request('POST', $this->baseUrl().'/v2/payments/captures/'.$providerTransactionId.'/refund', [
            'auth_bearer' => $token,
            'json' => [
                'amount' => ['currency_code' => 'EUR', 'value' => $amountStr],
            ],
        ]);

        /** @var array{id: string} $result */
        $result = $response->toArray();

        return $result['id'];
    }

    public function verifyWebhook(string $payload, string $signature): array
    {
        $event = json_decode($payload, true);
        if (!\is_array($event)) {
            throw new \RuntimeException('Invalid PayPal webhook payload');
        }

        // Verify signature via PayPal API
        if ($this->paypalWebhookId && $signature) {
            $this->verifyWebhookSignature($payload, $signature, $event['id'] ?? '');
        }

        $type = match ($event['event_type'] ?? '') {
            'PAYMENT.CAPTURE.COMPLETED' => 'payment.succeeded',
            'PAYMENT.CAPTURE.DENIED' => 'payment.failed',
            default => $event['event_type'],
        };

        $transactionId = $event['resource']['supplementary_data']['related_ids']['order_id']
            ?? $event['resource']['id'];

        return [
            'type' => $type,
            'transactionId' => $transactionId,
        ];
    }

    public function buildOnboardingUrl(string $hostProfileId): string
    {
        $params = http_build_query([
            'partnerId' => $this->paypalClientId,
            'returnToPartnerUrl' => $this->appBaseUrl.'/api/payment-provider/paypal/callback?state='.$hostProfileId,
            'product' => 'PPCP',
        ]);

        $base = $this->sandbox
            ? 'https://www.sandbox.paypal.com/bizsignup/partner/entry'
            : 'https://www.paypal.com/bizsignup/partner/entry';

        return $base.'?'.$params;
    }

    public function completeOnboarding(string $authorizationCode): string
    {
        return $authorizationCode;
    }

    public function buildDashboardUrl(string $connectedAccountId): string
    {
        return $this->sandbox
            ? 'https://www.sandbox.paypal.com/businessmanage/account/aboutBusiness'
            : 'https://www.paypal.com/businessmanage/account/aboutBusiness';
    }

    private function verifyWebhookSignature(string $payload, string $transmissionSig, string $webhookEventId): void
    {
        $token = $this->getAccessToken();

        $response = $this->httpClient->request('POST', $this->baseUrl().'/v1/notifications/verify-webhook-signature', [
            'auth_bearer' => $token,
            'json' => [
                'webhook_id' => $this->paypalWebhookId,
                'transmission_sig' => $transmissionSig,
                'webhook_event' => json_decode($payload, true),
            ],
        ]);

        /** @var array{verification_status: string} $result */
        $result = $response->toArray();

        if ('SUCCESS' !== $result['verification_status']) {
            throw new \RuntimeException('Invalid PayPal webhook signature');
        }
    }
}
