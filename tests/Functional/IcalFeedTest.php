<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\IcalFeedFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class IcalFeedTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testAddIcalFeed(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/ical-feeds', [
            'json' => [
                'url' => 'https://example.com/calendar.ics',
                'direction' => 'import',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testListIcalFeeds(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        IcalFeedFactory::createOne(['lodging' => $lodging]);

        $client = $this->authClient($host);
        $response = $client->request('GET', '/api/lodgings/'.$lodging->getId().'/ical-feeds');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }

    public function testDeleteIcalFeed(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $feed = IcalFeedFactory::createOne(['lodging' => $lodging]);

        $client = $this->authClient($host);
        $client->request('DELETE', '/api/ical_feeds/'.$feed->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testAddIcalFeedDeniedForCustomer(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/ical-feeds', [
            'json' => [
                'url' => 'https://example.com/calendar.ics',
                'direction' => 'import',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
