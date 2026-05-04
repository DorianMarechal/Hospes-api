<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Enum\BookingStatus;
use App\Enum\PaymentStatus;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\PaymentFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class WebhookTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    public function testStripeWebhookPaymentSucceeded(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $payment = PaymentFactory::createOne([
            'booking' => $booking,
            'status' => PaymentStatus::PENDING,
            'providerTransactionId' => 'txn_test_123',
        ]);

        $client = static::createClient();
        $client->request('POST', '/api/webhooks/stripe', [
            'headers' => ['Stripe-Signature' => 'test_sig'],
            'body' => json_encode([
                'type' => 'payment.succeeded',
                'transactionId' => 'txn_test_123',
            ]),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'processed']);
    }

    public function testStripeWebhookPaymentFailed(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $payment = PaymentFactory::createOne([
            'booking' => $booking,
            'status' => PaymentStatus::PENDING,
            'providerTransactionId' => 'txn_test_fail',
        ]);

        $client = static::createClient();
        $client->request('POST', '/api/webhooks/stripe', [
            'headers' => ['Stripe-Signature' => 'test_sig'],
            'body' => json_encode([
                'type' => 'payment.failed',
                'transactionId' => 'txn_test_fail',
            ]),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'processed']);
    }

    public function testStripeWebhookUnknownTransactionIgnored(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/webhooks/stripe', [
            'headers' => ['Stripe-Signature' => 'test_sig'],
            'body' => json_encode([
                'type' => 'payment.succeeded',
                'transactionId' => 'txn_nonexistent',
            ]),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'ignored']);
    }

    public function testPaypalWebhookPaymentSucceeded(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        PaymentFactory::createOne([
            'booking' => $booking,
            'status' => PaymentStatus::PENDING,
            'providerTransactionId' => 'pp_txn_123',
        ]);

        $client = static::createClient();
        $client->request('POST', '/api/webhooks/paypal', [
            'headers' => ['Paypal-Transmission-Sig' => 'test_sig'],
            'body' => json_encode([
                'type' => 'payment.succeeded',
                'transactionId' => 'pp_txn_123',
            ]),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'processed']);
    }
}
