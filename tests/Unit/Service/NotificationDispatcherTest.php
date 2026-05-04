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
use App\Enum\NotificationType;
use App\Service\MercurePublisher;
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
        $mercure = $this->createMock(MercurePublisher::class);
        $this->dispatcher = new NotificationDispatcher($this->em, $mercure);
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

    public function testBookingConfirmedCreatesNotificationForHost(): void
    {
        $booking = $this->createBookingWithHost();

        $this->dispatcher->bookingConfirmed($booking);

        $this->assertCount(1, $this->persisted);
        $notif = $this->persisted[0];
        $this->assertInstanceOf(Notification::class, $notif);
        $this->assertSame(NotificationType::BOOKING_CONFIRMED, $notif->getType());
        $this->assertStringContains('HOS-ABC12345-26', $notif->getContent());
        $this->assertStringContains('Chalet Montagne', $notif->getContent());
        $this->assertNotNull($notif->getParams());
        $this->assertSame('HOS-ABC12345-26', $notif->getParams()['reference']);
        $this->assertSame('Chalet Montagne', $notif->getParams()['lodging_name']);
    }

    public function testBookingConfirmedDoesNothingWhenNoHost(): void
    {
        $booking = new Booking();

        $this->dispatcher->bookingConfirmed($booking);

        $this->assertCount(0, $this->persisted);
    }

    public function testBookingCancelledNotifiesCustomerWhenHostCancels(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);

        $host = $booking->getLodging()->getHost()->getUser();
        $booking->setCancelledBy($host);

        $this->dispatcher->bookingCancelled($booking);

        $customerNotifs = array_filter($this->persisted, fn (Notification $n) => $n->getUser() === $customer);
        $this->assertCount(1, $customerNotifs);
    }

    public function testBookingCancelledNotifiesHostWhenCustomerCancels(): void
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

    public function testBookingCancelledNotifiesBothWhenNoCancelledBy(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);

        $this->dispatcher->bookingCancelled($booking);

        $this->assertCount(2, $this->persisted);
    }

    public function testBookingModifiedCreatesNotificationForHost(): void
    {
        $booking = $this->createBookingWithHost();

        $this->dispatcher->bookingModified($booking);

        $this->assertCount(1, $this->persisted);
        $this->assertSame(NotificationType::BOOKING_MODIFIED, $this->persisted[0]->getType());
    }

    public function testBookingExpiredCreatesNotificationForCustomer(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);

        $this->dispatcher->bookingExpired($booking);

        $this->assertCount(1, $this->persisted);
        $this->assertSame(NotificationType::BOOKING_EXPIRED, $this->persisted[0]->getType());
        $this->assertSame($customer, $this->persisted[0]->getUser());
        $this->assertStringContains('15 minutes', $this->persisted[0]->getContent());
    }

    public function testBookingExpiredDoesNothingWhenNoCustomer(): void
    {
        $booking = new Booking();

        $this->dispatcher->bookingExpired($booking);

        $this->assertCount(0, $this->persisted);
    }

    public function testStaffInvitedCreatesNotificationForHost(): void
    {
        $host = new User();
        $assignment = new StaffAssignment();
        $assignment->setHost($host);

        $this->dispatcher->staffInvited($assignment, 'staff@example.com');

        $this->assertCount(1, $this->persisted);
        $this->assertSame(NotificationType::STAFF_INVITED, $this->persisted[0]->getType());
        $this->assertStringContains('staff@example.com', $this->persisted[0]->getContent());
        $this->assertSame('staff@example.com', $this->persisted[0]->getParams()['email']);
    }

    public function testReviewReceivedCreatesNotificationForHost(): void
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
        $this->assertSame(NotificationType::REVIEW_RECEIVED, $this->persisted[0]->getType());
        $this->assertStringContains('4/5', $this->persisted[0]->getContent());
        $this->assertStringContains('Villa Soleil', $this->persisted[0]->getContent());
    }

    public function testMessageReceivedCreatesNotification(): void
    {
        $user = new User();

        $this->dispatcher->messageReceived($user, 'Chalet Neige');

        $this->assertCount(1, $this->persisted);
        $this->assertSame(NotificationType::MESSAGE_RECEIVED, $this->persisted[0]->getType());
        $this->assertStringContains('Chalet Neige', $this->persisted[0]->getContent());
    }

    public function testPaymentReceivedCreatesNotificationForHost(): void
    {
        $booking = $this->createBookingWithHost();

        $payment = new Payment();
        $payment->setBooking($booking);
        $payment->setAmount(15000);

        $this->dispatcher->paymentReceived($payment);

        $this->assertCount(1, $this->persisted);
        $this->assertSame(NotificationType::PAYMENT_RECEIVED, $this->persisted[0]->getType());
        $this->assertStringContains('150,00', $this->persisted[0]->getContent());
        $this->assertSame('15000', $this->persisted[0]->getParams()['amount']);
        $this->assertSame('EUR', $this->persisted[0]->getParams()['currency']);
    }

    public function testDepositReleasedCreatesNotificationForCustomer(): void
    {
        $booking = $this->createBookingWithHost();
        $customer = new User();
        $booking->setCustomer($customer);

        $deposit = new Deposit();
        $deposit->setBooking($booking);

        $this->dispatcher->depositReleased($deposit);

        $this->assertCount(1, $this->persisted);
        $this->assertSame(NotificationType::DEPOSIT_RELEASED, $this->persisted[0]->getType());
        $this->assertSame($customer, $this->persisted[0]->getUser());
    }

    private static function assertStringContains(string $needle, string $haystack): void
    {
        self::assertStringContainsString($needle, $haystack);
    }
}
