<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Enum\BookingStatus;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\ReviewFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AdminTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testAdminListUsers(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real();
        UserFactory::createOne();
        UserFactory::createOne();

        $client = $this->authClient($admin);
        $response = $client->request('GET', '/api/admin/users');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(3, \count($data['member']));
    }

    public function testNonAdminCannotListUsers(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminDeactivateUser(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real();
        $user = UserFactory::createOne();

        $client = $this->authClient($admin);
        $client->request('POST', '/api/admin/users/'.$user->getId().'/deactivate', [
            'json' => [],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testAdminReactivateUser(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real();
        $user = UserFactory::createOne(['isActive' => false]);

        $client = $this->authClient($admin);
        $client->request('POST', '/api/admin/users/'.$user->getId().'/reactivate', [
            'json' => [],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testAdminListBookings(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real();
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        BookingFactory::createOne(['lodging' => $lodging, 'customer' => $customer]);

        $client = $this->authClient($admin);
        $response = $client->request('GET', '/api/admin/bookings');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, \count($data['member']));
    }

    public function testAdminDeleteLodging(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real();
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $client = $this->authClient($admin);
        $client->request('DELETE', '/api/admin/lodgings/'.$lodging->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function testAdminListReviews(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real();
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::COMPLETED,
        ]);
        ReviewFactory::createOne([
            'booking' => $booking,
            'lodging' => $lodging,
            'customer' => $customer,
        ]);

        $client = $this->authClient($admin);
        $response = $client->request('GET', '/api/admin/reviews');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, \count($data['member']));
    }

    public function testAdminGetStats(): void
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real();

        $client = $this->authClient($admin);
        $client->request('GET', '/api/admin/stats');

        $this->assertResponseIsSuccessful();
    }
}
