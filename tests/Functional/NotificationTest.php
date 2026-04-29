<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\NotificationFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class NotificationTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testListNotifications(): void
    {
        $user = UserFactory::createOne()->_real();
        NotificationFactory::createOne(['user' => $user]);
        NotificationFactory::createOne(['user' => $user]);

        $client = $this->authClient($user);
        $response = $client->request('GET', '/api/me/notifications');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testMarkNotificationAsRead(): void
    {
        $user = UserFactory::createOne()->_real();
        $notification = NotificationFactory::createOne(['user' => $user]);

        $client = $this->authClient($user);
        $client->request('POST', '/api/notifications/'.$notification->getId().'/read', [
            'json' => [],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testMarkAllNotificationsAsRead(): void
    {
        $user = UserFactory::createOne()->_real();
        NotificationFactory::createOne(['user' => $user]);
        NotificationFactory::createOne(['user' => $user]);

        $client = $this->authClient($user);
        $client->request('POST', '/api/me/notifications/read-all', [
            'json' => [],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testCannotReadOtherUsersNotification(): void
    {
        $user1 = UserFactory::createOne()->_real();
        $user2 = UserFactory::createOne()->_real();
        $notification = NotificationFactory::createOne(['user' => $user1]);

        $client = $this->authClient($user2);
        $client->request('POST', '/api/notifications/'.$notification->getId().'/read', [
            'json' => [],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
