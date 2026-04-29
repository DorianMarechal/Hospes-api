<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Enum\BookingStatus;
use App\Enum\PaymentProvider;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\PaymentFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class PaymentTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testCreatePayment(): void
    {
        $hostProfile = HostProfileFactory::createOne([
            'paymentProvider' => PaymentProvider::STRIPE,
            'paymentProviderAccountId' => 'acct_test123',
        ]);
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $client = $this->authClient($customer);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/payments', [
            'json' => [
                'amount' => 35600,
                'method' => 'card',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testCreatePaymentDeniedForOtherCustomer(): void
    {
        $hostProfile = HostProfileFactory::createOne([
            'paymentProvider' => PaymentProvider::STRIPE,
            'paymentProviderAccountId' => 'acct_test123',
        ]);
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $other = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $client = $this->authClient($other);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/payments', [
            'json' => [
                'amount' => 35600,
                'method' => 'card',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListBookingPayments(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
        ]);
        PaymentFactory::createOne(['booking' => $booking]);

        $client = $this->authClient($customer);
        $response = $client->request('GET', '/api/bookings/'.$booking->getId().'/payments');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }

    public function testRefundPaymentAsHost(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
        ]);
        $payment = PaymentFactory::createOne(['booking' => $booking]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/payments/'.$payment->getId().'/refund', [
            'json' => ['reason' => 'Customer requested cancellation'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['refundReason' => 'Customer requested cancellation']);
    }

    public function testRefundDeniedForNonHost(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
        ]);
        $payment = PaymentFactory::createOne(['booking' => $booking]);

        $otherHost = HostProfileFactory::createOne()->getUser();
        $client = $this->authClient($otherHost);
        $client->request('POST', '/api/payments/'.$payment->getId().'/refund', [
            'json' => ['reason' => 'Unauthorized'],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
