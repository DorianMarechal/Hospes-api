<?php

namespace App\Tests\Integration;

use App\Entity\Booking;
use App\Entity\BookingNight;
use App\Enum\BookingStatus;
use App\Enum\CancellationPolicy;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BookingConstraintsTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function test_exclude_gist_prevents_overlapping_bookings(): void
    {
        $lodging = LodgingFactory::createOne();
        $customer1 = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $customer2 = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $checkin = new \DateTimeImmutable('+14 days');
        $checkout = new \DateTimeImmutable('+17 days');
        $now = new \DateTimeImmutable();

        // First booking
        BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer1,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'status' => BookingStatus::CONFIRMED,
        ]);

        // Second overlapping booking on same lodging — should trigger EXCLUDE constraint
        $booking2 = new Booking();
        $booking2->setLodging($lodging->_real());
        $booking2->setCustomer($customer2->_real());
        $booking2->setReference('OVERLAP'.bin2hex(random_bytes(4)));
        $booking2->setCheckin($checkin->modify('+1 day'));
        $booking2->setCheckout($checkout->modify('+1 day'));
        $booking2->setGuestsCount(2);
        $booking2->setNumberOfNights(3);
        $booking2->setNightsTotal(30000);
        $booking2->setCleaningFee(5000);
        $booking2->setTouristTaxTotal(600);
        $booking2->setDepositAmount(30000);
        $booking2->setTotalPrice(35600);
        $booking2->setCancellationPolicy(CancellationPolicy::MODERATE);
        $booking2->setStatus(BookingStatus::CONFIRMED);
        $booking2->setCreatedAt($now);
        $booking2->setUpdatedAt($now);

        $this->em->persist($booking2);

        $this->expectException(\Exception::class);
        $this->em->flush();
    }

    public function test_cancelled_booking_does_not_block_overlap(): void
    {
        $lodging = LodgingFactory::createOne();
        $customer1 = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $customer2 = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $checkin = new \DateTimeImmutable('+14 days');
        $checkout = new \DateTimeImmutable('+17 days');
        $now = new \DateTimeImmutable();

        // Cancelled booking
        BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer1,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'status' => BookingStatus::CANCELLED,
        ]);

        // Overlapping booking should be allowed because first is cancelled
        $booking2 = new Booking();
        $booking2->setLodging($lodging->_real());
        $booking2->setCustomer($customer2->_real());
        $booking2->setReference('NOBLOCK'.bin2hex(random_bytes(4)));
        $booking2->setCheckin($checkin);
        $booking2->setCheckout($checkout);
        $booking2->setGuestsCount(2);
        $booking2->setNumberOfNights(3);
        $booking2->setNightsTotal(30000);
        $booking2->setCleaningFee(5000);
        $booking2->setTouristTaxTotal(600);
        $booking2->setDepositAmount(30000);
        $booking2->setTotalPrice(35600);
        $booking2->setCancellationPolicy(CancellationPolicy::MODERATE);
        $booking2->setStatus(BookingStatus::CONFIRMED);
        $booking2->setCreatedAt($now);
        $booking2->setUpdatedAt($now);

        $this->em->persist($booking2);
        $this->em->flush();

        $this->assertNotNull($booking2->getId());
    }

    public function test_adjacent_bookings_are_allowed(): void
    {
        $lodging = LodgingFactory::createOne();
        $customer1 = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $customer2 = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $now = new \DateTimeImmutable();

        // Booking from 10th to 13th (checkout day J = checkin day J compatible)
        BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer1,
            'checkin' => new \DateTimeImmutable('+10 days'),
            'checkout' => new \DateTimeImmutable('+13 days'),
            'status' => BookingStatus::CONFIRMED,
        ]);

        // Booking from 13th to 16th — adjacent, should work with daterange exclusion
        $booking2 = new Booking();
        $booking2->setLodging($lodging->_real());
        $booking2->setCustomer($customer2->_real());
        $booking2->setReference('ADJACENT'.bin2hex(random_bytes(4)));
        $booking2->setCheckin(new \DateTimeImmutable('+13 days'));
        $booking2->setCheckout(new \DateTimeImmutable('+16 days'));
        $booking2->setGuestsCount(2);
        $booking2->setNumberOfNights(3);
        $booking2->setNightsTotal(30000);
        $booking2->setCleaningFee(5000);
        $booking2->setTouristTaxTotal(600);
        $booking2->setDepositAmount(30000);
        $booking2->setTotalPrice(35600);
        $booking2->setCancellationPolicy(CancellationPolicy::MODERATE);
        $booking2->setStatus(BookingStatus::CONFIRMED);
        $booking2->setCreatedAt($now);
        $booking2->setUpdatedAt($now);

        $this->em->persist($booking2);
        $this->em->flush();

        $this->assertNotNull($booking2->getId());
    }

    public function test_booking_reference_unique_constraint(): void
    {
        $lodging = LodgingFactory::createOne();
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $now = new \DateTimeImmutable();

        $ref = 'UNIQUE'.bin2hex(random_bytes(4));

        BookingFactory::createOne([
            'lodging' => $lodging,
            'customer' => $customer,
            'reference' => $ref,
            'checkin' => new \DateTimeImmutable('+30 days'),
            'checkout' => new \DateTimeImmutable('+33 days'),
        ]);

        // Duplicate reference
        $booking2 = new Booking();
        $booking2->setLodging($lodging->_real());
        $booking2->setCustomer($customer->_real());
        $booking2->setReference($ref);
        $booking2->setCheckin(new \DateTimeImmutable('+40 days'));
        $booking2->setCheckout(new \DateTimeImmutable('+43 days'));
        $booking2->setGuestsCount(2);
        $booking2->setNumberOfNights(3);
        $booking2->setNightsTotal(30000);
        $booking2->setCleaningFee(5000);
        $booking2->setTouristTaxTotal(600);
        $booking2->setDepositAmount(30000);
        $booking2->setTotalPrice(35600);
        $booking2->setCancellationPolicy(CancellationPolicy::MODERATE);
        $booking2->setStatus(BookingStatus::CONFIRMED);
        $booking2->setCreatedAt($now);
        $booking2->setUpdatedAt($now);

        $this->em->persist($booking2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }

    public function test_booking_night_unique_per_booking_and_date(): void
    {
        $booking = BookingFactory::createOne([
            'checkin' => new \DateTimeImmutable('+50 days'),
            'checkout' => new \DateTimeImmutable('+53 days'),
        ]);

        $date = new \DateTimeImmutable('+50 days');

        $night1 = new BookingNight();
        $night1->setBooking($booking->_real());
        $night1->setDate($date);
        $night1->setPrice(10000);
        $night1->setSource('base_week');
        $this->em->persist($night1);
        $this->em->flush();

        // Duplicate night for same booking/date
        $night2 = new BookingNight();
        $night2->setBooking($booking->_real());
        $night2->setDate($date);
        $night2->setPrice(10000);
        $night2->setSource('base_week');
        $this->em->persist($night2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }
}
