<?php

namespace App\Tests\Unit\Security;

use App\Entity\Booking;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\User;
use App\Security\Voter\BookingAccessVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Uid\Uuid;

class BookingAccessVoterTest extends TestCase
{
    private BookingAccessVoter $voter;

    protected function setUp(): void
    {
        $roleHierarchy = new RoleHierarchy([
            'ROLE_ADMIN' => ['ROLE_HOST', 'ROLE_CUSTOMER'],
            'ROLE_HOST' => ['ROLE_USER'],
            'ROLE_CUSTOMER' => ['ROLE_USER'],
            'ROLE_STAFF' => ['ROLE_USER'],
        ]);
        $this->voter = new BookingAccessVoter($roleHierarchy);
    }

    private function createToken(?User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function setId(object $entity, ?Uuid $id = null): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id ?? Uuid::v7());
    }

    private function createBookingWithCustomerAndHost(): array
    {
        $customer = new User();
        $this->setId($customer);
        $customer->setRoles(['ROLE_CUSTOMER']);

        $hostProfile = new HostProfile();
        $this->setId($hostProfile);

        $host = new User();
        $this->setId($host);
        $host->setRoles(['ROLE_HOST']);
        $host->setHostProfile($hostProfile);

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setLodging($lodging);

        return [$booking, $customer, $host];
    }

    public function test_view_granted_for_customer(): void
    {
        [$booking, $customer] = $this->createBookingWithCustomerAndHost();
        $token = $this->createToken($customer);

        $result = $this->voter->vote($token, $booking, [BookingAccessVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function test_view_granted_for_host(): void
    {
        [$booking, $customer, $host] = $this->createBookingWithCustomerAndHost();
        $token = $this->createToken($host);

        $result = $this->voter->vote($token, $booking, [BookingAccessVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function test_view_granted_for_admin(): void
    {
        [$booking] = $this->createBookingWithCustomerAndHost();
        $admin = new User();
        $this->setId($admin);
        $admin->setRoles(['ROLE_ADMIN']);
        $token = $this->createToken($admin);

        $result = $this->voter->vote($token, $booking, [BookingAccessVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function test_view_denied_for_other_customer(): void
    {
        [$booking] = $this->createBookingWithCustomerAndHost();
        $other = new User();
        $this->setId($other);
        $other->setRoles(['ROLE_CUSTOMER']);
        $token = $this->createToken($other);

        $result = $this->voter->vote($token, $booking, [BookingAccessVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function test_view_denied_for_other_host(): void
    {
        [$booking] = $this->createBookingWithCustomerAndHost();
        $otherHostProfile = new HostProfile();
        $this->setId($otherHostProfile);
        $otherHost = new User();
        $this->setId($otherHost);
        $otherHost->setRoles(['ROLE_HOST']);
        $otherHost->setHostProfile($otherHostProfile);
        $token = $this->createToken($otherHost);

        $result = $this->voter->vote($token, $booking, [BookingAccessVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function test_view_denied_for_anonymous(): void
    {
        [$booking] = $this->createBookingWithCustomerAndHost();
        $token = $this->createToken(null);

        $result = $this->voter->vote($token, $booking, [BookingAccessVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function test_cancel_granted_for_customer(): void
    {
        [$booking, $customer] = $this->createBookingWithCustomerAndHost();
        $token = $this->createToken($customer);

        $result = $this->voter->vote($token, $booking, [BookingAccessVoter::CANCEL]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function test_edit_granted_for_host(): void
    {
        [$booking, $customer, $host] = $this->createBookingWithCustomerAndHost();
        $token = $this->createToken($host);

        $result = $this->voter->vote($token, $booking, [BookingAccessVoter::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function test_abstains_for_wrong_attribute(): void
    {
        [$booking] = $this->createBookingWithCustomerAndHost();
        $token = $this->createToken(null);

        $result = $this->voter->vote($token, $booking, ['WRONG_ATTRIBUTE']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function test_abstains_for_wrong_subject(): void
    {
        $user = new User();
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $user, [BookingAccessVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
