<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;

trait ApiTestHelper
{
    private function loginAs(User $user): string
    {
        /** @var Client $client */
        $client = static::createClient();
        $response = $client->request('POST', '/api/login_check', [
            'json' => [
                'username' => $user->getEmail(),
                'password' => 'password',
            ],
        ]);

        $data = $response->toArray();

        return $data['token'];
    }

    private function authClient(User $user): Client
    {
        $token = $this->loginAs($user);

        return static::createClient([], [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);
    }

    private function assertJsonResponse(int $expectedCode, Response $response): void
    {
        $this->assertSame($expectedCode, $response->getStatusCode());
    }

    /**
     * @return array{lodging: \App\Entity\Lodging, host: User, customer: User, booking: \App\Entity\Booking}
     */
    private function createBookingFixture(): array
    {
        $hostProfile = HostProfileFactory::createOne();
        /** @var User $host */
        $host = $hostProfile->getUser();

        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        /** @var User $customer */
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
        ]);

        return [
            'lodging' => $lodging->_real(),
            'host' => $host,
            'customer' => $customer,
            'booking' => $booking->_real(),
        ];
    }
}
