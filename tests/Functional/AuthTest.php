<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AuthTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testRegisterCustomer(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/register', [
            'json' => [
                'email' => 'customer@example.com',
                'password' => 'Str0ng!Pass#2026',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'accountType' => 'customer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['email' => 'customer@example.com']);
    }

    public function testRegisterHost(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/register', [
            'json' => [
                'email' => 'host@example.com',
                'password' => 'Str0ng!Pass#2026',
                'firstName' => 'Marie',
                'lastName' => 'Martin',
                'accountType' => 'host',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['roles' => ['ROLE_HOST']]);
    }

    public function testRegisterDuplicateEmailReturns409(): void
    {
        UserFactory::createOne(['email' => 'taken@example.com']);

        $client = static::createClient();
        $client->request('POST', '/api/auth/register', [
            'json' => [
                'email' => 'taken@example.com',
                'password' => 'Str0ng!Pass#2026',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'accountType' => 'customer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testRegisterWeakPasswordReturns422(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/register', [
            'json' => [
                'email' => 'weak@example.com',
                'password' => 'short',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'accountType' => 'customer',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testLoginSuccess(): void
    {
        UserFactory::createOne(['email' => 'login@example.com']);

        $client = static::createClient();
        $response = $client->request('POST', '/api/login_check', [
            'json' => [
                'username' => 'login@example.com',
                'password' => 'password',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);
    }

    public function testLoginWrongPasswordReturns401(): void
    {
        UserFactory::createOne(['email' => 'login@example.com']);

        $client = static::createClient();
        $client->request('POST', '/api/login_check', [
            'json' => [
                'username' => 'login@example.com',
                'password' => 'wrong_password',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeReturnsAuthenticatedUser(): void
    {
        $user = UserFactory::createOne(['email' => 'me@example.com'])->_real();
        $client = $this->authClient($user);

        $client->request('GET', '/api/auth/me');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['email' => 'me@example.com']);
    }

    public function testMeReturns401WithoutToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me');

        $this->assertResponseStatusCodeSame(401);
    }
}
