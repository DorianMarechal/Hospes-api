<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\Deposit;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\Notification;
use App\Entity\Payment;
use App\Entity\Review;
use App\Entity\StaffAssignment;
use App\Entity\User;
use App\Service\NotificationDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class NotificationDispatcherTest extends TestCase
{
    private NotificationDispatcher $dispatcher;
    private EntityManagerInterface $em;

    /** @var Notification[] */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('persist')->willReturnCallback(function ($entity) {
            $this->persisted[] = $entity;
        });
        $this->dispatcher = new NotificationDispatcher($this->em);
        $this->persisted = [];
    }

    private function createBookingWithHost(): Booking
    {
        $host = new User();
        $hostProfile = new HostProfile();
        $hostProfile->setUser($host);

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);
        $lodging->setName('Chalet Montagne');

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setReference('HOS-ABC12345-26');

        return $booking;
    }

    public function test_booking_confirmed_creates_notification_for_host(): void
    {
        $booking = $this->createBookingWithHost();

        $this->dispatcher->bookingConfirmed($booking);

        $this->assertCount(1, $this->persisted);
        $notif = $this->persisted[0];
        $this->assertInstanceOf(Notification::class, $notif);
        $this->assertSame('booking_confirmed', $notif->getType());
        $this->assertStringContains('HOS-ABC12345-26', $notif->getContent());
        $this->assertStringContains('Chalet Montagne', $notif->getContent());
    }

    public function test_booking_confirmed_does_nothing_when_no_host(): void
    {
        $booking = new Booking();

        $this->dispatcher->bookingConfirmed($booking);

        $this->assertCount(0, $this->persisted);
    }

    public function test_booking_cancelled_notifies_customer_when_host_cancels(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);

        $host = $booking->getLodging()->getHost()->getUser();
        $booking->setCancelledBy($host);

        $this->dispatcher->bookingCancelled($booking);

        // Customer should be notified (not host since host cancelled)
        $customerNotifs = array_filter($this->persisted, fn (Notification $n) => $n->getUser() === $customer);
        $this->assertCount(1, $customerNotifs);
    }

    public function test_booking_cancelled_notifies_host_when_customer_cancels(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);
        $booking->setCancelledBy($customer);

        $this->dispatcher->bookingCancelled($booking);

        $host = $booking->getLodging()->getHost()->getUser();
        $hostNotifs = array_filter($this->persisted, fn (Notification $n) => $n->getUser() === $host);
        $this->assertCount(1, $hostNotifs);
    }

    public function test_booking_cancelled_notifies_both_when_no_cancelled_by(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);

        $this->dispatcher->bookingCancelled($booking);

        // Both host and customer notified
        $this->assertCount(2, $this->persisted);
    }

    public function test_booking_modified_creates_notification_for_host(): void
    {
        $booking = $this->createBookingWithHost();

        $this->dispatcher->bookingModified($booking);

        $this->assertCount(1, $this->persisted);
        $this->assertSame('booking_modified', $this->persisted[0]->getType());
    }

    public function test_booking_expired_creates_notification_for_customer(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);

        $this->dispatcher->bookingExpired($booking);

        $this->assertCount(1, $this->persisted);
        $this->assertSame('booking_expired', $this->persisted[0]->getType());
        $this->assertSame($customer, $this->persisted[0]->getUser());
        $this->assertStringContains('15 minutes', $this->persisted[0]->getContent());
    }

    public function test_booking_expired_does_nothing_when_no_customer(): void
    {
        $booking = new Booking();

        $this->dispatcher->bookingExpired($booking);

        $this->assertCount(0, $this->persisted);
    }

    public function test_staff_invited_creates_notification_for_host(): void
    {
        $host = new User();
        $assignment = new StaffAssignment();
        $assignment->setHost($host);

        $this->dispatcher->staffInvited($assignment, 'staff@example.com');

        $this->assertCount(1, $this->persisted);
        $this->assertSame('staff_invited', $this->persisted[0]->getType());
        $this->assertStringContains('staff@example.com', $this->persisted[0]->getContent());
    }

    public function test_review_received_creates_notification_for_host(): void
    {
        $host = new User();
        $hostProfile = new HostProfile();
        $hostProfile->setUser($host);

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);
        $lodging->setName('Villa Soleil');

        $review = new Review();
        $review->setLodging($lodging);
        $review->setRating(4);

        $this->dispatcher->reviewReceived($review);

        $this->assertCount(1, $this->persisted);
        $this->assertSame('review_received', $this->persisted[0]->getType());
        $this->assertStringContains('4/5', $this->persisted[0]->getContent());
        $this->assertStringContains('Villa Soleil', $this->persisted[0]->getContent());
    }

    public function test_message_received_creates_notification(): void
    {
        $user = new User();

        $this->dispatcher->messageReceived($user, 'Chalet Neige');

        $this->assertCount(1, $this->persisted);
        $this->assertSame('message_received', $this->persisted[0]->getType());
        $this->assertStringContains('Chalet Neige', $this->persisted[0]->getContent());
    }

    public function test_payment_received_creates_notification_for_host(): void
    {
        $booking = $this->createBookingWithHost();

        $payment = new Payment();
        $payment->setBooking($booking);
        $payment->setAmount(15000);

        $this->dispatcher->paymentReceived($payment);

        $this->assertCount(1, $this->persisted);
        $this->assertSame('payment_received', $this->persisted[0]->getType());
        $this->assertStringContains('150,00', $this->persisted[0]->getContent());
    }

    public function test_deposit_released_creates_notification_for_customer(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);

        $deposit = new Deposit();
        $deposit->setBooking($booking);

        $this->dispatcher->depositReleased($deposit);

        $this->assertCount(1, $this->persisted);
        $this->assertSame('deposit_released', $this->persisted[0]->getType());
        $this->assertSame($customer, $this->persisted[0]->getUser());
    }

    private static function assertStringContains(string $needle, string $haystack): void
    {
        self::assertStringContainsString($needle, $haystack);
    }
}
