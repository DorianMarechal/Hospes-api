<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LodgingTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function test_create_lodging_as_host(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $client = $this->authClient($host);

        $client->request('POST', '/api/lodgings', [
            'json' => [
                'name' => 'Gîte du Lac',
                'type' => 'gite',
                'description' => 'Un beau gîte au bord du lac',
                'capacity' => 6,
                'basePriceWeek' => 8000,
                'basePriceWeekend' => 10000,
                'cleaningFee' => 5000,
                'touristTaxPerPerson' => 100,
                'depositAmount' => 30000,
                'cancellationPolicy' => 'moderate',
                'checkinTime' => '15:00',
                'checkoutTime' => '11:00',
                'address' => '12 rue du Lac',
                'city' => 'Annecy',
                'postalCode' => '74000',
                'country' => 'FR',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['name' => 'Gîte du Lac']);
    }

    public function test_create_lodging_denied_for_customer(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $client = $this->authClient($customer);

        $client->request('POST', '/api/lodgings', [
            'json' => [
                'name' => 'Test',
                'type' => 'gite',
                'capacity' => 4,
                'basePriceWeek' => 8000,
                'basePriceWeekend' => 10000,
                'cancellationPolicy' => 'moderate',
                'checkinTime' => '15:00',
                'checkoutTime' => '11:00',
                'address' => '1 rue Test',
                'city' => 'Paris',
                'postalCode' => '75001',
                'country' => 'FR',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function test_get_my_lodgings(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        LodgingFactory::createOne(['host' => $hostProfile, 'name' => 'Mon Gîte']);

        $client = $this->authClient($host);
        $response = $client->request('GET', '/api/me/lodgings');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
    }

    public function test_delete_lodging_as_owner(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $client = $this->authClient($host);
        $client->request('DELETE', '/api/lodgings/'.$lodging->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    public function test_delete_lodging_denied_for_other_host(): void
    {
        $hostProfile1 = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile1]);

        $hostProfile2 = HostProfileFactory::createOne();
        $otherHost = $hostProfile2->getUser();

        $client = $this->authClient($otherHost);
        $client->request('DELETE', '/api/lodgings/'.$lodging->getId());

        $this->assertResponseStatusCodeSame(403);
    }
}
