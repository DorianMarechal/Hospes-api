<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StaffTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testInviteStaff(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        LodgingFactory::createOne(['host' => $hostProfile]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/me/staff', [
            'json' => [
                'email' => 'staff@example.com',
                'permissions' => ['can_view_bookings'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testListStaff(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();

        $client = $this->authClient($host);
        $response = $client->request('GET', '/api/me/staff');

        $this->assertResponseIsSuccessful();
    }

    public function testInviteStaffDeniedForCustomer(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        LodgingFactory::createOne(['host' => $hostProfile]);

        $customer = \App\Tests\Factory\UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $client = $this->authClient($customer);

        $client->request('POST', '/api/me/staff', [
            'json' => [
                'email' => 'staff@example.com',
                'permissions' => ['can_view_bookings'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
