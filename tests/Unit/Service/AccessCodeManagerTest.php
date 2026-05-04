<?php

namespace App\Tests\Unit\Service;

use App\Entity\AccessCode;
use App\Entity\Booking;
use App\Repository\AccessCodeRepository;
use App\Service\AccessCodeManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AccessCodeManagerTest extends TestCase
{
    public function testGenerateForBookingCreatesAccessCodeWithSixDigitCode(): void
    {
        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-06-01'));
        $booking->setCheckout(new \DateTimeImmutable('2026-06-05'));

        $repository = $this->createMock(AccessCodeRepository::class);
        $repository->expects($this->once())
            ->method('findByBooking')
            ->with($booking)
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');

        $manager = new AccessCodeManager($repository, $em);
        $accessCode = $manager->generateForBooking($booking);

        $this->assertNotNull($accessCode->getCode());
        $this->assertMatchesRegularExpression('/^\d{6}$/', $accessCode->getCode());
    }

    public function testGenerateForBookingSetsValidFromToCheckin(): void
    {
        $booking = new Booking();
        $checkin = new \DateTimeImmutable('2026-06-01');
        $checkout = new \DateTimeImmutable('2026-06-05');
        $booking->setCheckin($checkin);
        $booking->setCheckout($checkout);

        $repository = $this->createStub(AccessCodeRepository::class);
        $repository->method('findByBooking')->willReturn(null);

        $manager = new AccessCodeManager($repository, $this->createStub(EntityManagerInterface::class));
        $accessCode = $manager->generateForBooking($booking);

        $this->assertEquals($checkin, $accessCode->getValidFrom());
        $this->assertEquals($checkout, $accessCode->getValidTo());
    }

    public function testGenerateForBookingSetsBookingReference(): void
    {
        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-06-01'));
        $booking->setCheckout(new \DateTimeImmutable('2026-06-05'));

        $repository = $this->createStub(AccessCodeRepository::class);
        $repository->method('findByBooking')->willReturn(null);

        $manager = new AccessCodeManager($repository, $this->createStub(EntityManagerInterface::class));
        $accessCode = $manager->generateForBooking($booking);

        $this->assertSame($booking, $accessCode->getBooking());
    }

    public function testGenerateForBookingSetsCreatedAt(): void
    {
        $before = new \DateTimeImmutable();

        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-06-01'));
        $booking->setCheckout(new \DateTimeImmutable('2026-06-05'));

        $repository = $this->createStub(AccessCodeRepository::class);
        $repository->method('findByBooking')->willReturn(null);

        $manager = new AccessCodeManager($repository, $this->createStub(EntityManagerInterface::class));
        $accessCode = $manager->generateForBooking($booking);

        $after = new \DateTimeImmutable();

        $this->assertNotNull($accessCode->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $accessCode->getCreatedAt());
        $this->assertLessThanOrEqual($after, $accessCode->getCreatedAt());
    }

    public function testGenerateForBookingFallsBackToNowWhenCheckinIsNull(): void
    {
        $booking = new Booking();
        // no checkin / checkout set — both are null

        $repository = $this->createStub(AccessCodeRepository::class);
        $repository->method('findByBooking')->willReturn(null);

        $manager = new AccessCodeManager($repository, $this->createStub(EntityManagerInterface::class));

        $before = new \DateTimeImmutable();
        $accessCode = $manager->generateForBooking($booking);
        $after = new \DateTimeImmutable();

        $this->assertNotNull($accessCode->getValidFrom());
        $this->assertGreaterThanOrEqual($before, $accessCode->getValidFrom());
        $this->assertLessThanOrEqual($after, $accessCode->getValidFrom());
    }

    public function testGenerateForBookingReturnsExistingCodeIfAlreadyExists(): void
    {
        $booking = new Booking();
        $existing = new AccessCode();
        $existing->setCode('123456');

        $repository = $this->createMock(AccessCodeRepository::class);
        $repository->expects($this->once())
            ->method('findByBooking')
            ->with($booking)
            ->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');

        $manager = new AccessCodeManager($repository, $em);
        $result = $manager->generateForBooking($booking);

        $this->assertSame($existing, $result);
    }

    public function testRevokeForBookingSetsRevokedTrue(): void
    {
        $booking = new Booking();
        $accessCode = new AccessCode();
        $accessCode->setRevoked(false);

        $repository = $this->createMock(AccessCodeRepository::class);
        $repository->expects($this->once())
            ->method('findByBooking')
            ->with($booking)
            ->willReturn($accessCode);

        $manager = new AccessCodeManager($repository, $this->createStub(EntityManagerInterface::class));
        $manager->revokeForBooking($booking);

        $this->assertTrue($accessCode->isRevoked());
    }

    public function testRevokeForBookingDoesNothingIfAlreadyRevoked(): void
    {
        $booking = new Booking();
        $accessCode = new AccessCode();
        $accessCode->setRevoked(true);

        $repository = $this->createMock(AccessCodeRepository::class);
        $repository->expects($this->once())
            ->method('findByBooking')
            ->willReturn($accessCode);

        $manager = new AccessCodeManager($repository, $this->createStub(EntityManagerInterface::class));
        $manager->revokeForBooking($booking);

        $this->assertTrue($accessCode->isRevoked());
    }

    public function testRevokeForBookingDoesNothingIfNoAccessCodeExists(): void
    {
        $booking = new Booking();

        $repository = $this->createMock(AccessCodeRepository::class);
        $repository->expects($this->once())
            ->method('findByBooking')
            ->with($booking)
            ->willReturn(null);

        $manager = new AccessCodeManager($repository, $this->createStub(EntityManagerInterface::class));
        $manager->revokeForBooking($booking);

        $this->addToAssertionCount(1);
    }
}
