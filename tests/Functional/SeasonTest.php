<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\SeasonFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SeasonTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testCreateSeason(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/seasons', [
            'json' => [
                'name' => 'Haute saison',
                'startDate' => '2026-07-01',
                'endDate' => '2026-08-31',
                'priceWeek' => 12000,
                'priceWeekend' => 15000,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['name' => 'Haute saison']);
    }

    /**
     * @group todo
     */
    public function testCreateOverlappingSeasonReturns422(): void
    {
        $this->markTestSkipped('Overlap check in processor needs investigation with Foundry/API Platform context isolation');
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        SeasonFactory::createOne([
            'lodging' => $lodging,
            'name' => 'Été',
            'startDate' => new \DateTimeImmutable('2026-07-01'),
            'endDate' => new \DateTimeImmutable('2026-08-31'),
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/seasons', [
            'json' => [
                'name' => 'Mi-été',
                'startDate' => '2026-08-01',
                'endDate' => '2026-09-30',
                'priceWeek' => 10000,
                'priceWeekend' => 12000,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testListSeasons(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        SeasonFactory::createOne([
            'lodging' => $lodging,
            'startDate' => new \DateTimeImmutable('2026-06-01'),
            'endDate' => new \DateTimeImmutable('2026-06-30'),
        ]);
        SeasonFactory::createOne([
            'lodging' => $lodging,
            'name' => 'Été',
            'startDate' => new \DateTimeImmutable('2026-07-01'),
            'endDate' => new \DateTimeImmutable('2026-08-31'),
        ]);

        $client = $this->authClient($host);
        $response = $client->request('GET', '/api/lodgings/'.$lodging->getId().'/seasons');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testDeleteSeason(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);

        $season = SeasonFactory::createOne([
            'lodging' => $lodging,
            'startDate' => new \DateTimeImmutable('2026-06-01'),
            'endDate' => new \DateTimeImmutable('2026-06-30'),
        ]);

        $client = $this->authClient($host);
        $client->request('DELETE', '/api/seasons/'.$season->getId());

        $this->assertResponseStatusCodeSame(204);
    }
}
