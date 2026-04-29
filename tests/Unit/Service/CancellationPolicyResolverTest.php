<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\User;
use App\Enum\CancellationPolicy;
use App\Service\CancellationPolicyResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CancellationPolicyResolverTest extends TestCase
{
    private CancellationPolicyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CancellationPolicyResolver();
    }

    private function setId(object $entity, ?Uuid $id = null): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id ?? Uuid::v7());
    }

    private function createBooking(CancellationPolicy $policy, int $totalPrice, string $checkinDate, ?User $hostUser = null): array
    {
        $hostProfile = new HostProfile();
        $this->setId($hostProfile);

        $host = $hostUser ?? new User();
        $this->setId($host);
        $host->setHostProfile($hostProfile);

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);

        $customer = new User();
        $this->setId($customer);

        $booking = new Booking();
        $booking->setCancellationPolicy($policy);
        $booking->setTotalPrice($totalPrice);
        $booking->setCheckin(new \DateTimeImmutable($checkinDate));
        $booking->setLodging($lodging);
        $booking->setCustomer($customer);

        return [$booking, $customer, $host];
    }

    public function testFlexibleRefundWhenMoreThan24hBeforeCheckin(): void
    {
        [$booking, $customer] = $this->createBooking(CancellationPolicy::FLEXIBLE, 15000, '+3 days');

        $result = $this->resolver->resolve($booking, $customer);

        $this->assertTrue($result['eligible']);
        $this->assertSame(15000, $result['refundAmount']);
    }

    public function testFlexibleNoRefundWhenLessThan24hBeforeCheckin(): void
    {
        [$booking, $customer] = $this->createBooking(CancellationPolicy::FLEXIBLE, 15000, '+12 hours');

        $result = $this->resolver->resolve($booking, $customer);

        $this->assertFalse($result['eligible']);
        $this->assertSame(0, $result['refundAmount']);
    }

    public function testModerateRefundWhenMoreThan5DaysBeforeCheckin(): void
    {
        [$booking, $customer] = $this->createBooking(CancellationPolicy::MODERATE, 20000, '+10 days');

        $result = $this->resolver->resolve($booking, $customer);

        $this->assertTrue($result['eligible']);
        $this->assertSame(20000, $result['refundAmount']);
    }

    public function testModerateNoRefundWhenLessThan5DaysBeforeCheckin(): void
    {
        [$booking, $customer] = $this->createBooking(CancellationPolicy::MODERATE, 20000, '+3 days');

        $result = $this->resolver->resolve($booking, $customer);

        $this->assertFalse($result['eligible']);
        $this->assertSame(0, $result['refundAmount']);
    }

    public function testStrictNeverRefunds(): void
    {
        [$booking, $customer] = $this->createBooking(CancellationPolicy::STRICT, 25000, '+30 days');

        $result = $this->resolver->resolve($booking, $customer);

        $this->assertFalse($result['eligible']);
        $this->assertSame(0, $result['refundAmount']);
    }

    public function testHostCancellationAlwaysRefundsWithFlexible(): void
    {
        [$booking, $customer, $host] = $this->createBooking(CancellationPolicy::FLEXIBLE, 15000, '+1 hour');

        $result = $this->resolver->resolve($booking, $host);

        $this->assertTrue($result['eligible']);
        $this->assertSame(15000, $result['refundAmount']);
    }

    public function testHostCancellationAlwaysRefundsWithStrict(): void
    {
        [$booking, $customer, $host] = $this->createBooking(CancellationPolicy::STRICT, 25000, '+1 day');

        $result = $this->resolver->resolve($booking, $host);

        $this->assertTrue($result['eligible']);
        $this->assertSame(25000, $result['refundAmount']);
    }

    public function testFlexibleExactly24hBoundary(): void
    {
        // Exactly 24h should not be eligible (must be MORE than 24h)
        $now = new \DateTimeImmutable();
        $checkin = $now->modify('+24 hours');
        [$booking, $customer] = $this->createBooking(CancellationPolicy::FLEXIBLE, 10000, $checkin->format('Y-m-d H:i:s'));

        $result = $this->resolver->resolve($booking, $customer, $now);

        $this->assertFalse($result['eligible']);
    }

    public function testModerateExactly5DaysBoundary(): void
    {
        $now = new \DateTimeImmutable();
        $checkin = $now->modify('+5 days');
        [$booking, $customer] = $this->createBooking(CancellationPolicy::MODERATE, 10000, $checkin->format('Y-m-d H:i:s'));

        $result = $this->resolver->resolve($booking, $customer, $now);

        $this->assertFalse($result['eligible']);
    }

    public function testNullCheckinReturnsNotEligible(): void
    {
        $hostProfile = new HostProfile();
        $this->setId($hostProfile);
        $host = new User();
        $this->setId($host);
        $host->setHostProfile($hostProfile);

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);

        $customer = new User();
        $this->setId($customer);

        $booking = new Booking();
        $booking->setCancellationPolicy(CancellationPolicy::FLEXIBLE);
        $booking->setTotalPrice(10000);
        $booking->setLodging($lodging);
        $booking->setCustomer($customer);
        // No checkin set

        $result = $this->resolver->resolve($booking, $customer);

        $this->assertFalse($result['eligible']);
        $this->assertSame(0, $result['refundAmount']);
    }
}
