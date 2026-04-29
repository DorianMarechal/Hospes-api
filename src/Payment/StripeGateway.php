<?php

namespace App\Payment;

use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(
        private string $stripeSecretKey,
        private string $stripeWebhookSecret,
        private string $stripeClientId,
        private string $appBaseUrl,
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function createPayment(int $amountCents, string $currency, string $connectedAccountId, string $description): array
    {
        $paymentIntent = PaymentIntent::create([
            'amount' => $amountCents,
            'currency' => $currency,
            'description' => $description,
            'transfer_data' => [
                'destination' => $connectedAccountId,
            ],
        ]);

        return [
            'transactionId' => $paymentIntent->id,
            'clientSecret' => $paymentIntent->client_secret,
            'approvalUrl' => null,
        ];
    }

    public function refund(string $providerTransactionId, int $amountCents, string $connectedAccountId): string
    {
        $refund = Refund::create([
            'payment_intent' => $providerTransactionId,
            'amount' => $amountCents,
        ]);

        return $refund->id;
    }

    public function verifyWebhook(string $payload, string $signature): array
    {
        try {
            $event = Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (SignatureVerificationException $e) {
            throw new \RuntimeException('Invalid Stripe webhook signature: '.$e->getMessage());
        }

        $type = match ($event->type) {
            'payment_intent.succeeded' => 'payment.succeeded',
            'payment_intent.payment_failed' => 'payment.failed',
            default => $event->type,
        };

        /** @var PaymentIntent $object */
        $object = $event->data->object;

        return [
            'type' => $type,
            'transactionId' => $object->id,
        ];
    }

    public function buildOnboardingUrl(string $hostProfileId): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->stripeClientId,
            'scope' => 'read_write',
            'redirect_uri' => $this->appBaseUrl.'/api/payment-provider/stripe/callback',
            'state' => $hostProfileId,
        ]);

        return 'https://connect.stripe.com/oauth/authorize?'.$params;
    }

    public function completeOnboarding(string $authorizationCode): string
    {
        $response = \Stripe\OAuth::token([
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
        ]);

        /** @var string $stripeUserId */
        $stripeUserId = $response->stripe_user_id; // @phpstan-ignore property.notFound

        return $stripeUserId;
    }

    public function buildDashboardUrl(string $connectedAccountId): string
    {
        $loginLink = \Stripe\Account::createLoginLink($connectedAccountId);

        return $loginLink->url;
    }
}
