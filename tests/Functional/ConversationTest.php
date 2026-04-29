<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ConversationTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testCreateConversation(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/conversations', [
            'json' => [],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateDuplicateConversationReturns409(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/conversations', [
            'json' => [],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/conversations', [
            'json' => [],
        ]);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testSendMessage(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $response = $client->request('POST', '/api/lodgings/'.$lodging->getId().'/conversations', [
            'json' => [],
        ]);
        $convo = $response->toArray();

        $client->request('POST', '/api/conversations/'.$convo['id'].'/messages', [
            'json' => ['content' => 'Hello, is the lodging available?'],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testListConversations(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $client->request('POST', '/api/lodgings/'.$lodging->getId().'/conversations', [
            'json' => [],
        ]);

        $response = $client->request('GET', '/api/me/conversations');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }

    public function testMarkConversationAsRead(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($customer);
        $response = $client->request('POST', '/api/lodgings/'.$lodging->getId().'/conversations', [
            'json' => [],
        ]);
        $convo = $response->toArray();

        $client->request('POST', '/api/conversations/'.$convo['id'].'/read', [
            'json' => [],
        ]);
        $this->assertResponseIsSuccessful();
    }
}
