<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Enum\BookingStatus;
use App\Enum\ModificationRequestStatus;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\BookingModificationRequestFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BookingModificationRequestTest extends ApiTestCase
{
    use ApiTestHelper;
    use Factories;
    use ResetDatabase;

    public function testCreateModificationRequestAsCustomer(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        $client = $this->authClient($customer);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/modification-request', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'pending']);
    }

    public function testCreateModificationRequestAsHost(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/modification-request', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateModificationRequestDeniedForStranger(): void
    {
        $fixture = $this->createBookingFixture();
        $stranger = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $client = $this->authClient($stranger);
        $client->request('POST', '/api/bookings/'.$fixture['booking']->getId().'/modification-request', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateModificationRequestCancelledBookingReturns422(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CANCELLED,
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/modification-request', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateModificationRequestUnavailableDatesReturns409(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();
        $otherCustomer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $otherCustomer,
            'checkin' => new \DateTimeImmutable('+20 days'),
            'checkout' => new \DateTimeImmutable('+23 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/modification-request', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+20 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+23 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testDuplicatePendingRequestReturns409(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        BookingModificationRequestFactory::createOne([
            'booking' => $booking,
            'requestedBy' => $customer,
            'status' => ModificationRequestStatus::PENDING,
            'expiresAt' => new \DateTimeImmutable('+48 hours'),
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/bookings/'.$booking->getId().'/modification-request', [
            'json' => [
                'checkin' => (new \DateTimeImmutable('+25 days'))->format('Y-m-d'),
                'checkout' => (new \DateTimeImmutable('+28 days'))->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testAcceptModificationRequest(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        $modRequest = BookingModificationRequestFactory::createOne([
            'booking' => $booking,
            'requestedBy' => $customer,
            'proposedCheckin' => new \DateTimeImmutable('+20 days'),
            'proposedCheckout' => new \DateTimeImmutable('+23 days'),
            'status' => ModificationRequestStatus::PENDING,
            'expiresAt' => new \DateTimeImmutable('+48 hours'),
        ]);

        // L'hôte accepte (il n'est pas le demandeur)
        $client = $this->authClient($host);
        $client->request('POST', '/api/booking-modifications/'.$modRequest->getId().'/accept');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'accepted']);
    }

    public function testRejectModificationRequest(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'checkin' => new \DateTimeImmutable('+14 days'),
            'checkout' => new \DateTimeImmutable('+17 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        $modRequest = BookingModificationRequestFactory::createOne([
            'booking' => $booking,
            'requestedBy' => $customer,
            'status' => ModificationRequestStatus::PENDING,
            'expiresAt' => new \DateTimeImmutable('+48 hours'),
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/booking-modifications/'.$modRequest->getId().'/reject');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'rejected']);
    }

    public function testRequesterCannotAcceptOwnRequest(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $modRequest = BookingModificationRequestFactory::createOne([
            'booking' => $booking,
            'requestedBy' => $customer,
            'status' => ModificationRequestStatus::PENDING,
            'expiresAt' => new \DateTimeImmutable('+48 hours'),
        ]);

        $client = $this->authClient($customer);
        $client->request('POST', '/api/booking-modifications/'.$modRequest->getId().'/accept');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAcceptExpiredRequestReturns422(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $modRequest = BookingModificationRequestFactory::createOne([
            'booking' => $booking,
            'requestedBy' => $customer,
            'status' => ModificationRequestStatus::PENDING,
            'expiresAt' => new \DateTimeImmutable('-1 hour'),
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/booking-modifications/'.$modRequest->getId().'/accept');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAcceptAlreadyRejectedReturns422(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();
        $lodging = LodgingFactory::createOne(['host' => $hostProfile]);
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']])->_real();

        $booking = BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $modRequest = BookingModificationRequestFactory::createOne([
            'booking' => $booking,
            'requestedBy' => $customer,
            'status' => ModificationRequestStatus::REJECTED,
            'expiresAt' => new \DateTimeImmutable('+48 hours'),
            'respondedAt' => new \DateTimeImmutable(),
        ]);

        $client = $this->authClient($host);
        $client->request('POST', '/api/booking-modifications/'.$modRequest->getId().'/accept');

        $this->assertResponseStatusCodeSame(422);
    }
}
