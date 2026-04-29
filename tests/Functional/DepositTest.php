<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Enum\BookingStatus;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\DepositFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DepositTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testGetDeposit(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::COMPLETED,
        ]);
        DepositFactory::createOne(['booking' => $booking]);

        $client = $this->authClient($customer);
        $client->request('GET', '/api/bookings/'.$booking->getId().'/deposit');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'held']);
    }

    public function testRetainDeposit(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::COMPLETED,
        ]);
        DepositFactory::createOne(['booking' => $booking, 'amount' => 30000]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/deposit/retain', [
            'json' => [
                'retainedAmount' => 15000,
                'reason' => 'Damaged furniture',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'retainedAmount' => 15000,
            'status' => 'partially_retained',
        ]);
    }

    public function testReleaseDeposit(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::COMPLETED,
        ]);
        DepositFactory::createOne(['booking' => $booking]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/deposit/release', [
            'json' => [],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'released']);
    }
}
