<?php

namespace App\Tests\Unit\Service;

use App\Entity\BlockedDate;
use App\Entity\Booking;
use App\Entity\Lodging;
use App\Entity\Season;
use App\Enum\BookingStatus;
use App\Service\OrphanProtectionChecker;
use PHPUnit\Framework\TestCase;

class OrphanProtectionCheckerTest extends TestCase
{
    private OrphanProtectionChecker $checker;
    private Lodging $lodging;

    protected function setUp(): void
    {
        $this->checker = new OrphanProtectionChecker();
        $this->lodging = new Lodging();
        $this->lodging->setMinStay(3);
        $this->lodging->setOrphanProtection(true);
    }

    private function createBooking(
        string $checkin,
        string $checkout,
        BookingStatus $status = BookingStatus::CONFIRMED,
    ): Booking {
        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable($checkin));
        $booking->setCheckout(new \DateTimeImmutable($checkout));
        $booking->setStatus($status);

        return $booking;
    }

    private function createBlockedDate(string $start, string $end): BlockedDate
    {
        $blocked = new BlockedDate();
        $blocked->setStartDate(new \DateTimeImmutable($start));
        $blocked->setEndDate(new \DateTimeImmutable($end));

        return $blocked;
    }

    // OP-1 : Protection désactivée → toujours OK
    public function testDisabledProtectionAlwaysPasses(): void
    {
        $this->lodging->setOrphanProtection(false);

        $existing = $this->createBooking('2026-07-10', '2026-07-13');

        // Crée un gap de 1 nuit (9→10) qui serait orphelin
        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-08'),
            new \DateTimeImmutable('2026-07-09'),
            [$existing],
            [],
            [],
        );

        $this->assertTrue(true);
    }

    // OP-2 : Gap avant < min_stay → exception
    public function testOrphanGapBeforeThrows(): void
    {
        $existing = $this->createBooking('2026-07-15', '2026-07-20');

        // Réservation du 10 au 13 → gap de 2 nuits (13→15) < min_stay(3)
        $this->expectException(\InvalidArgumentException::class);

        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-13'),
            [$existing],
            [],
            [],
        );
    }

    // OP-3 : Gap après < min_stay → exception
    public function testOrphanGapAfterThrows(): void
    {
        $existing = $this->createBooking('2026-07-05', '2026-07-10');

        // Réservation du 12 au 16 → gap de 2 nuits (10→12) < min_stay(3)
        $this->expectException(\InvalidArgumentException::class);

        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-12'),
            new \DateTimeImmutable('2026-07-16'),
            [$existing],
            [],
            [],
        );
    }

    // OP-4 : Gap >= min_stay → OK
    public function testGapEqualToMinStayPasses(): void
    {
        $existing = $this->createBooking('2026-07-15', '2026-07-20');

        // Réservation du 08 au 12 → gap de 3 nuits (12→15) = min_stay(3) → OK
        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-08'),
            new \DateTimeImmutable('2026-07-12'),
            [$existing],
            [],
            [],
        );

        $this->assertTrue(true);
    }

    // OP-5 : Comble exactement un trou existant → toujours accepté
    public function testFillsExactGapAlwaysAccepted(): void
    {
        $before = $this->createBooking('2026-07-05', '2026-07-10');
        $after = $this->createBooking('2026-07-12', '2026-07-17');

        // Réservation du 10 au 12 → comble exactement le trou, 2 nuits < min_stay(3) mais accepté
        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-12'),
            [$before, $after],
            [],
            [],
        );

        $this->assertTrue(true);
    }

    // OP-6 : Pas de voisin → pas de gap → OK
    public function testNoNeighborsPasses(): void
    {
        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-13'),
            [],
            [],
            [],
        );

        $this->assertTrue(true);
    }

    // OP-7 : BlockedDate compte comme période occupée
    public function testBlockedDateCreatesOrphan(): void
    {
        $blocked = $this->createBlockedDate('2026-07-15', '2026-07-20');

        // Gap de 2 nuits (13→15) < min_stay(3)
        $this->expectException(\InvalidArgumentException::class);

        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-13'),
            [],
            [$blocked],
            [],
        );
    }

    // OP-8 : Réservation annulée ignorée
    public function testCancelledBookingIgnored(): void
    {
        $cancelled = $this->createBooking('2026-07-15', '2026-07-20', BookingStatus::CANCELLED);

        // Gap de 2 nuits mais le voisin est annulé → pas de gap réel
        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-13'),
            [$cancelled],
            [],
            [],
        );

        $this->assertTrue(true);
    }

    // OP-9 : min_stay saisonnier plus restrictif
    public function testSeasonalMinStayUsedWhenStricter(): void
    {
        $this->lodging->setMinStay(2);
        $season = new Season();
        $season->setStartDate(new \DateTimeImmutable('2026-07-01'));
        $season->setEndDate(new \DateTimeImmutable('2026-07-31'));
        $season->setMinStay(4);

        $existing = $this->createBooking('2026-07-15', '2026-07-20');

        // Gap de 3 nuits (12→15), min_stay lodging=2 mais saison=4 → orphelin
        $this->expectException(\InvalidArgumentException::class);

        $this->checker->check(
            $this->lodging,
            new \DateTimeImmutable('2026-07-08'),
            new \DateTimeImmutable('2026-07-12'),
            [$existing],
            [],
            [$season],
        );
    }
}
