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

class ReviewTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testCreateReviewAfterCompletedStay(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::COMPLETED,
        ]);

        $client = $this->authClient($customer);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/review', [
            'json' => [
                'rating' => 4,
                'comment' => 'Great stay, lovely place!',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['rating' => 4]);
    }

    public function testCreateReviewBeforeCheckoutReturns400(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $client = $this->authClient($customer);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/review', [
            'json' => [
                'rating' => 5,
                'comment' => 'Great!',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateDuplicateReviewReturns400(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::COMPLETED,
        ]);

        $client = $this->authClient($customer);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/review', [
            'json' => ['rating' => 4, 'comment' => 'Nice stay'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/bookings/'.$booking->getId().'/review', [
            'json' => ['rating' => 5, 'comment' => 'Very nice stay'],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testListLodgingReviews(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking1 = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::COMPLETED,
        ]);
        ReviewFactory::createOne([
            'booking' => $booking1,
            'lodging' => $lodging,
            'customer' => $customer,
        ]);

        $client = static::createClient();
        $response = $client->request('GET', '/api/lodgings/'.$lodging->getId().'/reviews');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }

    public function testHostRespondsToReview(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::COMPLETED,
        ]);
        $review = ReviewFactory::createOne([
            'booking' => $booking,
            'lodging' => $lodging,
            'customer' => $customer,
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/reviews/'.$review->getId().'/response', [
            'json' => ['response' => 'Thank you for your kind words!'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hostResponse' => 'Thank you for your kind words!']);
    }

    public function testListMyReviews(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
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

        $client = $this->authClient($customer);
        $response = $client->request('GET', '/api/me/reviews');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }

    public function testAdminDeletesReview(): void
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
        $review = ReviewFactory::createOne([
            'booking' => $booking,
            'lodging' => $lodging,
            'customer' => $customer,
        ]);

        $client = $this->authClient($admin);
        $client->request('DELETE', '/api/reviews/'.$review->getId());

        $this->assertResponseStatusCodeSame(204);
    }
}
