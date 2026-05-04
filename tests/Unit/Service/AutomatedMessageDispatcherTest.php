<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\MessageTemplate;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\MessageChannel;
use App\Enum\MessageTemplateTrigger;
use App\Repository\AutomatedMessageLogRepository;
use App\Repository\BookingRepository;
use App\Repository\MessageTemplateRepository;
use App\Service\AutomatedMessageDispatcher;
use App\Service\EmailSender;
use App\Service\NotificationDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Uid\Uuid;

class AutomatedMessageDispatcherTest extends TestCase
{
    private MessageTemplateRepository&MockObject $templateRepository;
    private AutomatedMessageLogRepository&MockObject $logRepository;
    private BookingRepository&MockObject $bookingRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private EmailSender&MockObject $emailSender;
    private NotificationDispatcher&MockObject $notificationDispatcher;
    private MessageBusInterface&MockObject $messageBus;
    private LoggerInterface&MockObject $logger;
    private AutomatedMessageDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->templateRepository = $this->createMock(MessageTemplateRepository::class);
        $this->logRepository = $this->createMock(AutomatedMessageLogRepository::class);
        $this->bookingRepository = $this->createMock(BookingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->emailSender = $this->createMock(EmailSender::class);
        $this->notificationDispatcher = $this->createMock(NotificationDispatcher::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->dispatcher = new AutomatedMessageDispatcher(
            $this->templateRepository,
            $this->logRepository,
            $this->bookingRepository,
            $this->entityManager,
            $this->emailSender,
            $this->notificationDispatcher,
            $this->messageBus,
            $this->logger,
        );
    }

    private function setId(object $entity, ?Uuid $id = null): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id ?? Uuid::v7());
    }

    private function createBookingWithCustomer(): Booking
    {
        $hostProfile = new HostProfile();
        $lodging = new Lodging();
        $lodging->setHost($hostProfile);
        $lodging->setName('Chalet Test');

        $customer = new User();
        $customer->setEmail('guest@example.com');

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setCustomer($customer);
        $booking->setReference('HOS-TEST0001-26');
        $this->setId($booking);

        return $booking;
    }

    private function createTemplate(int $delayMinutes = 0, string $channel = 'email'): MessageTemplate
    {
        $template = new MessageTemplate();
        $template->setName('Test template');
        $template->setTriggerType(MessageTemplateTrigger::BOOKING_CONFIRMED);
        $template->setSubject('Bonjour {guest_name}');
        $template->setBody('Votre réservation {reference} est confirmée.');
        $template->setChannels([$channel]);
        $template->setDelayMinutes($delayMinutes);
        $this->setId($template);

        return $template;
    }

    // AMD-1: delay=0 sends immediately via emailSender
    public function testDispatchForBookingEventWithDelayZeroSendsImmediately(): void
    {
        $booking = $this->createBookingWithCustomer();
        $template = $this->createTemplate(delayMinutes: 0);

        $this->templateRepository
            ->method('findEnabledByTriggerAndLodging')
            ->willReturn([$template]);

        $this->logRepository
            ->method('hasAlreadySent')
            ->willReturn(false);

        $this->messageBus->expects($this->never())->method('dispatch');
        $this->emailSender->expects($this->once())->method('sendAutomatedMessage');
        $this->entityManager->expects($this->once())->method('persist');

        $this->dispatcher->dispatchForBookingEvent($booking, MessageTemplateTrigger::BOOKING_CONFIRMED);
    }

    // AMD-2: delay>0 dispatches via Messenger with a DelayStamp
    public function testDispatchForBookingEventWithDelayDispatchesViaMessenger(): void
    {
        $booking = $this->createBookingWithCustomer();
        $template = $this->createTemplate(delayMinutes: 30);

        $this->templateRepository
            ->method('findEnabledByTriggerAndLodging')
            ->willReturn([$template]);

        $this->emailSender->expects($this->never())->method('sendAutomatedMessage');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (Envelope $envelope) {
                $stamps = $envelope->all(DelayStamp::class);

                return count($stamps) === 1 && $stamps[0]->getDelay() === 30 * 60 * 1000;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchForBookingEvent($booking, MessageTemplateTrigger::BOOKING_CONFIRMED);
    }

    // AMD-3: processScheduledMessages handles CHECKIN_MINUS_1D trigger
    public function testProcessScheduledMessagesHandlesCheckinMinus1D(): void
    {
        $booking = $this->createBookingWithCustomer();
        $template = $this->createTemplate(delayMinutes: 0, channel: 'email');
        $template->setTriggerType(MessageTemplateTrigger::CHECKIN_MINUS_1D);

        $this->templateRepository
            ->method('findEnabledByTrigger')
            ->willReturnCallback(function (MessageTemplateTrigger $trigger) use ($template) {
                return $trigger === MessageTemplateTrigger::CHECKIN_MINUS_1D ? [$template] : [];
            });

        $tomorrow = (new \DateTimeImmutable())->modify('+1 day')->setTime(0, 0);
        $booking->setCheckin($tomorrow);
        $booking->setStatus(BookingStatus::CONFIRMED);

        $this->bookingRepository
            ->method('createQueryBuilder')
            ->willReturn($this->createMockQueryBuilderReturning([$booking]));

        $this->logRepository->method('hasAlreadySent')->willReturn(false);
        $this->emailSender->expects($this->once())->method('sendAutomatedMessage');
        $this->entityManager->expects($this->atLeastOnce())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->notificationDispatcher->expects($this->once())->method('publishPendingNotifications');

        $count = $this->dispatcher->processScheduledMessages();

        $this->assertSame(1, $count);
    }

    // AMD-4: processScheduledMessages handles CHECKOUT_PLUS_1D trigger
    public function testProcessScheduledMessagesHandlesCheckoutPlus1D(): void
    {
        $booking = $this->createBookingWithCustomer();
        $template = $this->createTemplate(delayMinutes: 0, channel: 'email');
        $template->setTriggerType(MessageTemplateTrigger::CHECKOUT_PLUS_1D);

        $this->templateRepository
            ->method('findEnabledByTrigger')
            ->willReturnCallback(function (MessageTemplateTrigger $trigger) use ($template) {
                return $trigger === MessageTemplateTrigger::CHECKOUT_PLUS_1D ? [$template] : [];
            });

        $yesterday = (new \DateTimeImmutable())->modify('-1 day')->setTime(0, 0);
        $booking->setCheckout($yesterday);
        $booking->setStatus(BookingStatus::COMPLETED);

        $this->bookingRepository
            ->method('createQueryBuilder')
            ->willReturn($this->createMockQueryBuilderReturning([$booking]));

        $this->logRepository->method('hasAlreadySent')->willReturn(false);
        $this->emailSender->expects($this->once())->method('sendAutomatedMessage');
        $this->entityManager->expects($this->once())->method('flush');
        $this->notificationDispatcher->expects($this->once())->method('publishPendingNotifications');

        $count = $this->dispatcher->processScheduledMessages();

        $this->assertSame(1, $count);
    }

    // AMD-5: template not matching booking's lodging is skipped (cron path via templateMatchesBooking)
    public function testProcessScheduledMessagesSkipsTemplateForDifferentLodging(): void
    {
        $booking = $this->createBookingWithCustomer();
        $bookingLodging = $booking->getLodging();
        $this->setId($bookingLodging);

        // Template scoped to a different lodging (different UUID)
        $otherLodging = new Lodging();
        $otherLodging->setName('Autre logement');
        $this->setId($otherLodging);

        $template = $this->createTemplate(delayMinutes: 0, channel: 'email');
        $template->setTriggerType(MessageTemplateTrigger::CHECKIN_MINUS_1D);
        $template->setLodging($otherLodging);

        $this->templateRepository
            ->method('findEnabledByTrigger')
            ->willReturnCallback(function (MessageTemplateTrigger $trigger) use ($template) {
                return $trigger === MessageTemplateTrigger::CHECKIN_MINUS_1D ? [$template] : [];
            });

        $tomorrow = (new \DateTimeImmutable())->modify('+1 day')->setTime(0, 0);
        $booking->setCheckin($tomorrow);
        $booking->setStatus(BookingStatus::CONFIRMED);

        $this->bookingRepository
            ->method('createQueryBuilder')
            ->willReturn($this->createMockQueryBuilderReturning([$booking]));

        $this->logRepository->method('hasAlreadySent')->willReturn(false);

        $this->emailSender->expects($this->never())->method('sendAutomatedMessage');
        $this->entityManager->expects($this->once())->method('flush');

        $count = $this->dispatcher->processScheduledMessages();

        $this->assertSame(0, $count);
    }

    // AMD-6: already-sent messages are not re-sent
    public function testDispatchForBookingEventDoesNotResendAlreadySentMessage(): void
    {
        $booking = $this->createBookingWithCustomer();
        $template = $this->createTemplate(delayMinutes: 0);

        $this->templateRepository
            ->method('findEnabledByTriggerAndLodging')
            ->willReturn([$template]);

        $this->logRepository
            ->method('hasAlreadySent')
            ->willReturn(true);

        $this->emailSender->expects($this->never())->method('sendAutomatedMessage');
        $this->entityManager->expects($this->never())->method('persist');

        $this->dispatcher->dispatchForBookingEvent($booking, MessageTemplateTrigger::BOOKING_CONFIRMED);
    }

    // AMD-7: template with unknown channel value is skipped
    public function testDispatchForBookingEventSkipsUnknownChannel(): void
    {
        $booking = $this->createBookingWithCustomer();
        $template = $this->createTemplate(delayMinutes: 0, channel: 'INVALID_CHANNEL');

        $this->templateRepository
            ->method('findEnabledByTriggerAndLodging')
            ->willReturn([$template]);

        $this->logRepository->method('hasAlreadySent')->willReturn(false);

        $this->emailSender->expects($this->never())->method('sendAutomatedMessage');
        $this->notificationDispatcher->expects($this->never())->method('automatedMessage');
        $this->entityManager->expects($this->never())->method('persist');

        $this->dispatcher->dispatchForBookingEvent($booking, MessageTemplateTrigger::BOOKING_CONFIRMED);
    }

    // AMD-8: no lodging on booking exits early without template lookup
    public function testDispatchForBookingEventWithNoLodgingDoesNothing(): void
    {
        $booking = new Booking();
        // No lodging set

        $this->templateRepository->expects($this->never())->method('findEnabledByTriggerAndLodging');
        $this->emailSender->expects($this->never())->method('sendAutomatedMessage');

        $this->dispatcher->dispatchForBookingEvent($booking, MessageTemplateTrigger::BOOKING_CONFIRMED);
    }

    // AMD-9: in_app channel routes to notificationDispatcher->automatedMessage
    public function testDispatchForBookingEventWithInAppChannelCallsNotificationDispatcher(): void
    {
        $booking = $this->createBookingWithCustomer();
        $template = $this->createTemplate(delayMinutes: 0, channel: MessageChannel::IN_APP->value);

        $this->templateRepository
            ->method('findEnabledByTriggerAndLodging')
            ->willReturn([$template]);

        $this->logRepository->method('hasAlreadySent')->willReturn(false);

        $this->emailSender->expects($this->never())->method('sendAutomatedMessage');
        $this->notificationDispatcher
            ->expects($this->once())
            ->method('automatedMessage')
            ->with(
                $this->isInstanceOf(User::class),
                $this->callback(fn (mixed $v) => \is_string($v)),
                $this->callback(fn (mixed $v) => \is_string($v)),
                $booking,
            );
        $this->entityManager->expects($this->once())->method('persist');

        $this->dispatcher->dispatchForBookingEvent($booking, MessageTemplateTrigger::BOOKING_CONFIRMED);
    }

    /**
     * Returns a mock query builder chain that produces the given result array.
     */
    private function createMockQueryBuilderReturning(array $results): \Doctrine\ORM\QueryBuilder
    {
        $query = $this->getMockBuilder(\Doctrine\ORM\Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('getResult')->willReturn($results);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }
}
