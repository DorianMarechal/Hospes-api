<?php

namespace App\Tests\Unit\Security;

use App\Entity\Booking;
use App\Entity\BookingModificationRequest;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\User;
use App\Security\Voter\ModificationRequestVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Uid\Uuid;

class ModificationRequestVoterTest extends TestCase
{
    private ModificationRequestVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new ModificationRequestVoter(new RoleHierarchy(['ROLE_ADMIN' => ['ROLE_USER']]));
    }

    public function testViewGrantedForCustomer(): void
    {
        $customerId = Uuid::v7();
        $customer = $this->createUser($customerId, ['ROLE_CUSTOMER']);
        $request = $this->createModificationRequest($customerId, Uuid::v7(), $customerId);

        $token = new UsernamePasswordToken($customer, 'main', $customer->getRoles());
        $result = $this->voter->vote($token, $request, [ModificationRequestVoter::VIEW]);

        $this->assertSame(1, $result);
    }

    public function testViewGrantedForHost(): void
    {
        $hostProfileId = Uuid::v7();
        $hostUserId = Uuid::v7();
        $customerId = Uuid::v7();
        $host = $this->createUser($hostUserId, ['ROLE_HOST'], $hostProfileId);
        $request = $this->createModificationRequest($customerId, $hostProfileId, $customerId);

        $token = new UsernamePasswordToken($host, 'main', $host->getRoles());
        $result = $this->voter->vote($token, $request, [ModificationRequestVoter::VIEW]);

        $this->assertSame(1, $result);
    }

    public function testViewDeniedForStranger(): void
    {
        $strangerId = Uuid::v7();
        $stranger = $this->createUser($strangerId, ['ROLE_CUSTOMER']);
        $request = $this->createModificationRequest(Uuid::v7(), Uuid::v7(), Uuid::v7());

        $token = new UsernamePasswordToken($stranger, 'main', $stranger->getRoles());
        $result = $this->voter->vote($token, $request, [ModificationRequestVoter::VIEW]);

        $this->assertSame(-1, $result);
    }

    public function testRespondDeniedForRequester(): void
    {
        $customerId = Uuid::v7();
        $customer = $this->createUser($customerId, ['ROLE_CUSTOMER']);
        $request = $this->createModificationRequest($customerId, Uuid::v7(), $customerId);

        $token = new UsernamePasswordToken($customer, 'main', $customer->getRoles());
        $result = $this->voter->vote($token, $request, [ModificationRequestVoter::RESPOND]);

        $this->assertSame(-1, $result);
    }

    public function testRespondGrantedForOtherParty(): void
    {
        $hostProfileId = Uuid::v7();
        $hostUserId = Uuid::v7();
        $customerId = Uuid::v7();
        $host = $this->createUser($hostUserId, ['ROLE_HOST'], $hostProfileId);
        // Customer made the request, host responds
        $request = $this->createModificationRequest($customerId, $hostProfileId, $customerId);

        $token = new UsernamePasswordToken($host, 'main', $host->getRoles());
        $result = $this->voter->vote($token, $request, [ModificationRequestVoter::RESPOND]);

        $this->assertSame(1, $result);
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(Uuid $id, array $roles, ?Uuid $hostProfileId = null): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getRoles')->willReturn($roles);

        if (null !== $hostProfileId) {
            $hostProfile = $this->createMock(HostProfile::class);
            $hostProfile->method('getId')->willReturn($hostProfileId);
            $user->method('getHostProfile')->willReturn($hostProfile);
        } else {
            $user->method('getHostProfile')->willReturn(null);
        }

        return $user;
    }

    private function createModificationRequest(Uuid $customerId, Uuid $hostProfileId, Uuid $requestedById): BookingModificationRequest
    {
        $customer = $this->createMock(User::class);
        $customer->method('getId')->willReturn($customerId);

        $hostProfile = $this->createMock(HostProfile::class);
        $hostProfile->method('getId')->willReturn($hostProfileId);

        $lodging = $this->createMock(Lodging::class);
        $lodging->method('getHost')->willReturn($hostProfile);

        $booking = $this->createMock(Booking::class);
        $booking->method('getCustomer')->willReturn($customer);
        $booking->method('getLodging')->willReturn($lodging);

        $requestedBy = $this->createMock(User::class);
        $requestedBy->method('getId')->willReturn($requestedById);

        $request = $this->createMock(BookingModificationRequest::class);
        $request->method('getBooking')->willReturn($booking);
        $request->method('getRequestedBy')->willReturn($requestedBy);

        return $request;
    }
}
