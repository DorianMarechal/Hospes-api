<?php

namespace App\Tests\Unit\Service;

use App\Entity\BlockedDate;
use App\Entity\Booking;
use App\Entity\Lodging;
use App\Entity\Season;
use App\Enum\BookingStatus;
use App\Service\AvailabilityResolver;
use PHPUnit\Framework\TestCase;

class AvailabilityResolverTest extends TestCase
{
    private AvailabilityResolver $resolver;
    private Lodging $lodging;

    protected function setUp(): void
    {
        $this->resolver = new AvailabilityResolver();
        $this->lodging = new Lodging();
        $this->lodging->setMinStay(1);
        $this->lodging->setMaxStay(14);
    }

    private function createBooking(
        string $checkin,
        string $checkout,
        BookingStatus $status = BookingStatus::CONFIRMED,
        ?\DateTimeImmutable $expiresAt = null,
        ?int $id = null,
    ): Booking {
        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable($checkin));
        $booking->setCheckout(new \DateTimeImmutable($checkout));
        $booking->setStatus($status);
        if ($expiresAt !== null) {
            $booking->setExpiresAt($expiresAt);
        }
        if ($id !== null) {
            $reflection = new \ReflectionProperty($booking, 'id');
            $reflection->setValue($booking, $id);
        }

        return $booking;
    }

    private function createBlockedDate(string $start, string $end): BlockedDate
    {
        $blocked = new BlockedDate();
        $blocked->setStartDate(new \DateTimeImmutable($start));
        $blocked->setEndDate(new \DateTimeImmutable($end));

        return $blocked;
    }

    private function createSeason(
        string $start,
        string $end,
        ?int $minStay = null,
        ?int $maxStay = null,
    ): Season {
        $season = new Season();
        $season->setStartDate(new \DateTimeImmutable($start));
        $season->setEndDate(new \DateTimeImmutable($end));
        $season->setMinStay($minStay);
        $season->setMaxStay($maxStay);

        return $season;
    }

    // AR-1 : Chevauchement partiel avec reservation existante → false
    public function testOverlapWithExistingBookingReturnsFalse(): void
    {
        $existing = $this->createBooking('2026-07-10', '2026-07-13');

        $result = $this->resolver->isAvailable(
            $this->lodging,
            new \DateTimeImmutable('2026-07-12'),
            new \DateTimeImmutable('2026-07-15'),
            [$existing],
            [],
            null,
        );

        $this->assertFalse($result);
    }

    // AR-2 : Check-out J = check-in J → true (convention nuitee)
    public function testCheckoutDayEqualsCheckinDayReturnsTrue(): void
    {
        $existing = $this->createBooking('2026-07-10', '2026-07-13');

        $result = $this->resolver->isAvailable(
            $this->lodging,
            new \DateTimeImmutable('2026-07-13'),
            new \DateTimeImmutable('2026-07-16'),
            [$existing],
            [],
            null,
        );

        $this->assertTrue($result);
    }

    // AR-3 : Dates bloquees → false
    public function testBlockedDatesReturnsFalse(): void
    {
        $blocked = $this->createBlockedDate('2026-07-10', '2026-07-13');

        $result = $this->resolver->isAvailable(
            $this->lodging,
            new \DateTimeImmutable('2026-07-12'),
            new \DateTimeImmutable('2026-07-15'),
            [],
            [$blocked],
            null,
        );

        $this->assertFalse($result);
    }

    // AR-5 : Pending active (TTL non expiree) bloque les dates → false
    public function testActivePendingBlocksDates(): void
    {
        $pending = $this->createBooking(
            '2026-07-10',
            '2026-07-13',
            BookingStatus::PENDING,
            new \DateTimeImmutable('+30 minutes'),
        );

        $result = $this->resolver->isAvailable(
            $this->lodging,
            new \DateTimeImmutable('2026-07-11'),
            new \DateTimeImmutable('2026-07-14'),
            [$pending],
            [],
            null,
        );

        $this->assertFalse($result);
    }

    // AR-6 : Pending expiree → ignoree, dates disponibles
    public function testExpiredPendingIsIgnored(): void
    {
        $expiredPending = $this->createBooking(
            '2026-07-10',
            '2026-07-13',
            BookingStatus::PENDING,
            new \DateTimeImmutable('-1 hour'),
        );

        $result = $this->resolver->isAvailable(
            $this->lodging,
            new \DateTimeImmutable('2026-07-11'),
            new \DateTimeImmutable('2026-07-14'),
            [$expiredPending],
            [],
            null,
        );

        $this->assertTrue($result);
    }

    public function testCancelledBookingIsIgnored(): void
    {
        $cancelled = $this->createBooking('2026-07-10', '2026-07-13', BookingStatus::CANCELLED);

        $result = $this->resolver->isAvailable(
            $this->lodging,
            new \DateTimeImmutable('2026-07-11'),
            new \DateTimeImmutable('2026-07-14'),
            [$cancelled],
            [],
            null,
        );

        $this->assertTrue($result);
    }

    // AR-9/10 : Modification → exclut la resa modifiee du check dispo
    public function testExcludedBookingIsIgnored(): void
    {
        $existing = $this->createBooking('2026-07-10', '2026-07-13', BookingStatus::CONFIRMED, null, 42);

        $result = $this->resolver->isAvailable(
            $this->lodging,
            new \DateTimeImmutable('2026-07-11'),
            new \DateTimeImmutable('2026-07-14'),
            [$existing],
            [],
            42,
        );

        $this->assertTrue($result);
    }

    public function testNoDatesConflictReturnsTrue(): void
    {
        $existing = $this->createBooking('2026-07-10', '2026-07-13');

        $result = $this->resolver->isAvailable(
            $this->lodging,
            new \DateTimeImmutable('2026-07-20'),
            new \DateTimeImmutable('2026-07-23'),
            [$existing],
            [],
            null,
        );

        $this->assertTrue($result);
    }

    // AR-11 : Duree < min_stay → exception
    public function testStayBelowMinStayThrowsException(): void
    {
        $this->lodging->setMinStay(3);

        $this->expectException(\InvalidArgumentException::class);

        $this->resolver->validateStayDuration(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-12'),
            [],
        );
    }

    // AR-12 : Duree > max_stay → exception
    public function testStayAboveMaxStayThrowsException(): void
    {
        $this->lodging->setMaxStay(7);

        $this->expectException(\InvalidArgumentException::class);

        $this->resolver->validateStayDuration(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-20'),
            [],
        );
    }

    // AR-13 : Sejour multi-saisons, min_stay differents → le plus restrictif
    public function testMultiSeasonUsesStrictestMinStay(): void
    {
        $basseSaison = $this->createSeason('2026-07-01', '2026-07-15', 2);
        $hauteSaison = $this->createSeason('2026-07-15', '2026-07-31', 5);

        $this->expectException(\InvalidArgumentException::class);

        $this->resolver->validateStayDuration(
            $this->lodging,
            new \DateTimeImmutable('2026-07-14'),
            new \DateTimeImmutable('2026-07-17'),
            [$basseSaison, $hauteSaison],
        );
    }

    public function testValidStayDurationDoesNotThrow(): void
    {
        $this->lodging->setMinStay(2);
        $this->lodging->setMaxStay(14);

        $season = $this->createSeason('2026-07-01', '2026-07-31', 3);

        $this->resolver->validateStayDuration(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-15'),
            [$season],
        );

        $this->assertTrue(true);
    }
}
