<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StatsTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testHostGetsStats(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        BookingFactory::createOne(['lodging' => $lodging, 'customer' => $customer]);

        $client = $this->authClient($host);
        $client->request('GET', '/api/me/stats');

        $this->assertResponseIsSuccessful();
    }

    public function testHostGetsLodgingStats(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $client = $this->authClient($host);
        $client->request('GET', '/api/me/lodgings/'.$lodging->getId().'/stats');

        $this->assertResponseIsSuccessful();
    }

    public function testCustomerCannotAccessStats(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('GET', '/api/me/stats');

        $this->assertResponseStatusCodeSame(403);
    }
}
