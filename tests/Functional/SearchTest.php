<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Enum\BookingStatus;
use App\Enum\LodgingType;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SearchTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    private function searchUrl(string $checkin, string $checkout, array $extra = []): string
    {
        $params = array_merge(['checkin' => $checkin, 'checkout' => $checkout], $extra);

        return '/api/availability?'.http_build_query($params);
    }

    private function dates(): array
    {
        return [
            (new \DateTimeImmutable('+30 days'))->format('Y-m-d'),
            (new \DateTimeImmutable('+33 days'))->format('Y-m-d'),
        ];
    }

    public function testSearchReturnsAvailableLodgings(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        LodgingFactory::createOne(['host' => $hostProfile, 'isActive' => true]);

        [$checkin, $checkout] = $this->dates();
        $client = static::createClient();
        $response = $client->request('GET', $this->searchUrl($checkin, $checkout));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, \count($data['member']));
    }

    public function testSearchExcludesUnavailableLodgings(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile, 'isActive' => true]);
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
        $response = $client->request('GET', $this->searchUrl($checkin->format('Y-m-d'), $checkout->format('Y-m-d')));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $lodgingIds = array_map(fn ($m) => $m['lodgingId'], $data['member']);
        $this->assertNotContains($lodging->getId()->toRfc4122(), $lodgingIds);
    }

    public function testSearchFilterByCity(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        LodgingFactory::createOne(['host' => $hostProfile, 'city' => 'Chamonix', 'isActive' => true]);
        LodgingFactory::createOne(['host' => $hostProfile, 'city' => 'Nice', 'isActive' => true]);

        [$checkin, $checkout] = $this->dates();
        $client = static::createClient();
        $response = $client->request('GET', $this->searchUrl($checkin, $checkout, ['city' => 'Chamonix']));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        foreach ($data['member'] as $member) {
            $this->assertSame('Chamonix', $member['city']);
        }
    }

    public function testSearchFilterByType(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        LodgingFactory::createOne(['host' => $hostProfile, 'type' => LodgingType::CABIN, 'isActive' => true]);
        LodgingFactory::createOne(['host' => $hostProfile, 'type' => LodgingType::APARTMENT, 'isActive' => true]);

        [$checkin, $checkout] = $this->dates();
        $client = static::createClient();
        $response = $client->request('GET', $this->searchUrl($checkin, $checkout, ['type' => 'cabin']));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        foreach ($data['member'] as $member) {
            $this->assertSame('cabin', $member['type']);
        }
    }

    public function testSearchFilterByCapacity(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        LodgingFactory::createOne(['host' => $hostProfile, 'capacity' => 2, 'isActive' => true]);
        LodgingFactory::createOne(['host' => $hostProfile, 'capacity' => 8, 'isActive' => true]);

        [$checkin, $checkout] = $this->dates();
        $client = static::createClient();
        $response = $client->request('GET', $this->searchUrl($checkin, $checkout, ['capacity' => '6']));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        foreach ($data['member'] as $member) {
            $this->assertGreaterThanOrEqual(6, $member['capacity']);
        }
    }

    public function testSearchMissingDatesReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/availability');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchInvalidDateFormatReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/availability?checkin=not-a-date&checkout=2026-12-15');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchCheckoutBeforeCheckinReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/availability?checkin=2026-12-15&checkout=2026-12-10');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchExcludesInactiveLodgings(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $inactive = LodgingFactory::createOne(['host' => $hostProfile, 'isActive' => false]);

        [$checkin, $checkout] = $this->dates();
        $client = static::createClient();
        $response = $client->request('GET', $this->searchUrl($checkin, $checkout));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $lodgingIds = array_map(fn ($m) => $m['lodgingId'], $data['member']);
        $this->assertNotContains($inactive->getId()->toRfc4122(), $lodgingIds);
    }
}
