<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\BlockedDateFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BlockedDateTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function test_create_blocked_date(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/blocked-dates', [
            'json' => [
                'startDate' => (new \DateTimeImmutable('+1 month'))->format('Y-m-d'),
                'endDate' => (new \DateTimeImmutable('+1 month +3 days'))->format('Y-m-d'),
                'reason' => 'Travaux',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function test_list_blocked_dates(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        BlockedDateFactory::createOne([
            'lodging' => $lodging,
            'startDate' => new \DateTimeImmutable('+1 month'),
            'endDate' => new \DateTimeImmutable('+1 month +2 days'),
        ]);

        $client = $this->authClient($host);
        $response = $client->request('GET', '/api/lodgings/'.$lodging->getId().'/blocked-dates');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
    }

    public function test_delete_blocked_date(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $blocked = BlockedDateFactory::createOne([
            'lodging' => $lodging,
            'startDate' => new \DateTimeImmutable('+1 month'),
            'endDate' => new \DateTimeImmutable('+1 month +2 days'),
        ]);

        $client = $this->authClient($host);
        $client->request('DELETE', '/api/blocked-dates/'.$blocked->getId());

        $this->assertResponseStatusCodeSame(204);
    }
}
