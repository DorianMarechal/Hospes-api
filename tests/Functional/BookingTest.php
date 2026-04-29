<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BookingTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testCreateBooking(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $checkin = (new \DateTimeImmutable('+14 days'))->format('Y-m-d');
        $checkout = (new \DateTimeImmutable('+17 days'))->format('Y-m-d');

        $client = $this->authClient($customer);
        $client->request('POST', '/api/bookings', [
            'json' => [
                'lodgingId' => $lodging->getId()->toRfc4122(),
                'checkin' => $checkin,
                'checkout' => $checkout,
                'guestsCount' => 2,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testCreateBookingDeniedForHost(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/bookings', [
            'json' => [
                'lodgingId' => $lodging->getId()->toRfc4122(),
                'checkin' => (new \DateTimeImmutable('+14 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+17 days'))->format('Y-m-d'),
                'guestsCount' => 2,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetBookingAsCustomer(): void
    {
        $fixture = $this->createBookingFixture();
        $client = $this->authClient($fixture['customer']);

        $client->request('GET', '/api/bookings/'.$fixture['booking']->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['reference' => $fixture['booking']->getReference()]);
    }

    public function testGetBookingDeniedForOtherCustomer(): void
    {
        $fixture = $this->createBookingFixture();
        $other = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($other);
        $client->request('GET', '/api/bookings/'.$fixture['booking']->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetBookingAsHost(): void
    {
        $fixture = $this->createBookingFixture();
        $client = $this->authClient($fixture['host']);

        $client->request('GET', '/api/bookings/'.$fixture['booking']->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testCancelBookingAsCustomer(): void
    {
        $fixture = $this->createBookingFixture();
        $client = $this->authClient($fixture['customer']);

        $client->request('POST', '/api/bookings/'.$fixture['booking']->getId().'/cancel', [
            'json' => ['reason' => 'Plans changed'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'cancelled']);
    }

    public function testListMyBookings(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        BookingFactory::createOne(['customer' => $customer, 'lodging' => $lodging]);
        BookingFactory::createOne(['customer' => $customer, 'lodging' => $lodging]);

        $client = $this->authClient($customer);
        $response = $client->request('GET', '/api/me/bookings');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }
}
