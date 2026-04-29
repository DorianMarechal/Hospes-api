<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Enum\BookingStatus;
use App\Tests\Factory\BlockedDateFactory;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AvailabilityTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testCheckAvailabilityReturnsAvailable(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $checkin = (new \DateTimeImmutable('+30 days'))->format('Y-m-d');
        $checkout = (new \DateTimeImmutable('+33 days'))->format('Y-m-d');

        $client = static::createClient();
        $response = $client->request('GET', '/api/lodgings/'.$lodging->getId().'/availability?checkin='.$checkin.'&checkout='.$checkout);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['available' => true]);
    }

    public function testCheckAvailabilityReturnsUnavailableWithBooking(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $checkin = new \DateTimeImmutable('+30 days');
        $checkout = new \DateTimeImmutable('+33 days');

        BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $client = static::createClient();
        $response = $client->request('GET', '/api/lodgings/'.$lodging->getId().'/availability?checkin='.$checkin->format('Y-m-d').'&checkout='.$checkout->format('Y-m-d'));

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['available' => false]);
    }

    public function testCheckAvailabilityReturnsUnavailableWithBlockedDates(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $startDate = new \DateTimeImmutable('+30 days');
        $endDate = new \DateTimeImmutable('+35 days');

        BlockedDateFactory::createOne([
            'lodging' => $lodging,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $client = static::createClient();
        $response = $client->request('GET', '/api/lodgings/'.$lodging->getId().'/availability?checkin='.$startDate->format('Y-m-d').'&checkout='.$endDate->format('Y-m-d'));

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['available' => false]);
    }

    public function testAvailabilitySearch(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        LodgingFactory::createOne(['host' => $hostProfile, 'city' => 'Chamonix', 'isActive' => true]);
        LodgingFactory::createOne(['host' => $hostProfile, 'city' => 'Nice', 'isActive' => true]);

        $checkin = (new \DateTimeImmutable('+30 days'))->format('Y-m-d');
        $checkout = (new \DateTimeImmutable('+33 days'))->format('Y-m-d');

        $client = static::createClient();
        $response = $client->request('GET', '/api/availability?checkin='.$checkin.'&checkout='.$checkout);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(2, \count($data['member']));
    }

    public function testAvailabilitySearchMissingDatesReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/availability');

        $this->assertResponseStatusCodeSame(400);
    }
}
