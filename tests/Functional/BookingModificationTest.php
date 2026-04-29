<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Enum\BookingStatus;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BookingModificationTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testModifyBookingDatesAsHost(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        $newCheckin = (new \DateTimeImmutable('+20 days'))->format('Y-m-d');
        $newCheckout = (new \DateTimeImmutable('+23 days'))->format('Y-m-d');

        $client = $this->authClient($host);
        $client->request('PUT', '/api/bookings/'.$booking->getId().'/dates', [
            'json' => [
                'checkin' => $newCheckin,
                'checkout' => $newCheckout,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'checkin' => $newCheckin.'T00:00:00+00:00',
            'checkout' => $newCheckout.'T00:00:00+00:00',
        ]);
    }

    public function testModifyBookingDatesDeniedForOtherHost(): void
    {
        $fixture = $this->createBookingFixture();
        $otherHost = HostProfileFactory::createOne()->getUser();

        $client = $this->authClient($otherHost);
        $client->request('PUT', '/api/bookings/'.$fixture['booking']->getId().'/dates', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testModifyCancelledBookingReturns422(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CANCELLED,
        ]);

        $client = $this->authClient($host);
        $client->request('PUT', '/api/bookings/'.$booking->getId().'/dates', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testModifyBookingToUnavailableDatesReturns409(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $otherCustomer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        // Another booking occupying the target dates
        BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $otherCustomer,
            'checkin' => new \DateTimeImmutable('+20 days'),
            'checkout' => new \DateTimeImmutable('+23 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        $client = $this->authClient($host);
        $client->request('PUT', '/api/bookings/'.$booking->getId().'/dates', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testModifyBookingDeniedForAnonymous(): void
    {
        $fixture = $this->createBookingFixture();

        $client = static::createClient();
        $client->request('PUT', '/api/bookings/'.$fixture['booking']->getId().'/dates', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
