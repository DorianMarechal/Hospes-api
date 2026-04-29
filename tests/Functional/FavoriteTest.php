<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class FavoriteTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testAddFavorite(): void
    {
        $lodging = LodgingFactory::createOne();
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('POST', '/api/me/favorites', [
            'json' => [
                'lodgingId' => $lodging->getId()->toRfc4122(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testAddDuplicateFavoriteReturns400(): void
    {
        $lodging = LodgingFactory::createOne();
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('POST', '/api/me/favorites', [
            'json' => [
                'lodgingId' => $lodging->getId()->toRfc4122(),
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/me/favorites', [
            'json' => [
                'lodgingId' => $lodging->getId()->toRfc4122(),
            ],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testListFavorites(): void
    {
        $lodging1 = LodgingFactory::createOne();
        $lodging2 = LodgingFactory::createOne();
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('POST', '/api/me/favorites', [
            'json' => ['lodgingId' => $lodging1->getId()->toRfc4122()],
        ]);
        $client->request('POST', '/api/me/favorites', [
            'json' => ['lodgingId' => $lodging2->getId()->toRfc4122()],
        ]);

        $response = $client->request('GET', '/api/me/favorites');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testDeleteFavorite(): void
    {
        $lodging = LodgingFactory::createOne();
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $response = $client->request('POST', '/api/me/favorites', [
            'json' => ['lodgingId' => $lodging->getId()->toRfc4122()],
        ]);
        $data = $response->toArray();

        $client->request('DELETE', '/api/favorites/'.$data['id']);
        $this->assertResponseStatusCodeSame(204);
    }
}
