<?php

namespace App\Service;

use App\Entity\AutomatedMessageLog;
use App\Entity\Booking;
use App\Entity\MessageTemplate;
use App\Enum\BookingStatus;
use App\Enum\MessageChannel;
use App\Enum\MessageTemplateTrigger;
use App\Message\DispatchAutomatedMessageMessage;
use App\Repository\AutomatedMessageLogRepository;
use App\Repository\BookingRepository;
use App\Repository\MessageTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class AutomatedMessageDispatcher
{
    public function __construct(
        private MessageTemplateRepository $templateRepository,
        private AutomatedMessageLogRepository $logRepository,
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $entityManager,
        private EmailSender $emailSender,
        private NotificationDispatcher $notificationDispatcher,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Called when a booking event occurs. Dispatches immediately or schedules via Messenger.
     */
    public function dispatchForBookingEvent(Booking $booking, MessageTemplateTrigger $trigger): void
    {
        $lodging = $booking->getLodging();
        if (null === $lodging) {
            return;
        }

        $templates = $this->templateRepository->findEnabledByTriggerAndLodging($trigger, $lodging);

        foreach ($templates as $template) {
            if ($template->getDelayMinutes() > 0) {
                $this->scheduleDelayed($template, $booking);
            } else {
                $this->sendTemplate($template, $booking);
            }
        }
    }

    /**
     * Cron: process time-based triggers (checkin_minus_1d, checkin_minus_3h, checkout_plus_1d).
     */
    public function processScheduledMessages(): int
    {
        $count = 0;
        $now = new \DateTimeImmutable();

        $count += $this->processTimeTrigger(MessageTemplateTrigger::CHECKIN_MINUS_1D, $now);
        $count += $this->processTimeTrigger(MessageTemplateTrigger::CHECKIN_SAME_DAY, $now);
        $count += $this->processTimeTrigger(MessageTemplateTrigger::CHECKOUT_PLUS_1D, $now);

        $this->entityManager->flush();
        $this->notificationDispatcher->publishPendingNotifications();

        return $count;
    }

    private function processTimeTrigger(MessageTemplateTrigger $trigger, \DateTimeImmutable $now): int
    {
        $templates = $this->templateRepository->findEnabledByTrigger($trigger);
        if (empty($templates)) {
            return 0;
        }

        $bookings = $this->findBookingsForTimeTrigger($trigger, $now);
        $count = 0;

        foreach ($bookings as $booking) {
            foreach ($templates as $template) {
                if (!$this->templateMatchesBooking($template, $booking)) {
                    continue;
                }

                if ($this->sendTemplate($template, $booking)) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    private function scheduleDelayed(MessageTemplate $template, Booking $booking): void
    {
        $templateId = $template->getId();
        $bookingId = $booking->getId();

        if (null === $templateId || null === $bookingId) {
            return;
        }

        $message = new DispatchAutomatedMessageMessage(
            (string) $templateId,
            (string) $bookingId,
        );

        $this->messageBus->dispatch(new Envelope($message, [
            new DelayStamp($template->getDelayMinutes() * 60 * 1000),
        ]));
    }

    private function sendTemplate(MessageTemplate $template, Booking $booking): bool
    {
        $customer = $booking->getCustomer();
        if (null === $customer) {
            return false;
        }

        $channels = $template->getChannels();
        $sent = false;

        foreach ($channels as $channelValue) {
            $channel = MessageChannel::tryFrom($channelValue);
            if (null === $channel) {
                continue;
            }

            if ($this->logRepository->hasAlreadySent($template, $booking, $channel)) {
                continue;
            }

            $subject = $this->substituteVariables($template->getSubject(), $booking);
            $body = $this->substituteVariables($template->getBody(), $booking);

            if (MessageChannel::SMS === $channel) {
                $this->logger->info('SMS channel not implemented yet', [
                    'booking' => $booking->getReference(),
                    'subject' => $subject,
                ]);
                continue;
            }

            if (MessageChannel::EMAIL === $channel) {
                $this->emailSender->sendAutomatedMessage(
                    $customer->getEmail(),
                    $subject,
                    $body,
                );
            } elseif (MessageChannel::IN_APP === $channel) {
                $this->notificationDispatcher->automatedMessage(
                    $customer,
                    $subject,
                    $body,
                    $booking,
                );
            }

            $this->logMessage($template, $booking, $channel);
            $sent = true;
        }

        return $sent;
    }

    private function logMessage(MessageTemplate $template, Booking $booking, MessageChannel $channel): void
    {
        $log = new AutomatedMessageLog();
        $log->setMessageTemplate($template);
        $log->setBooking($booking);
        $log->setTriggerType($template->getTriggerType());
        $log->setChannel($channel);
        $log->setSentAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
    }

    private function substituteVariables(string $text, Booking $booking): string
    {
        $lodging = $booking->getLodging();
        $customer = $booking->getCustomer();

        $variables = [
            '{guest_name}' => trim(($customer?->getFirstName() ?? '').' '.($customer?->getLastName() ?? '')),
            '{lodging_name}' => $lodging?->getName() ?? '',
            '{checkin_date}' => $booking->getCheckin()?->format('d/m/Y') ?? '',
            '{checkout_date}' => $booking->getCheckout()?->format('d/m/Y') ?? '',
            '{reference}' => $booking->getReference() ?? '',
            '{checkin_time}' => $lodging?->getCheckinTime()?->format('H:i') ?? '',
            '{address}' => $lodging?->getAddress() ?? '',
        ];

        return str_replace(array_keys($variables), array_values($variables), $text);
    }

    /**
     * @return Booking[]
     */
    private function findBookingsForTimeTrigger(MessageTemplateTrigger $trigger, \DateTimeImmutable $now): array
    {
        return match ($trigger) {
            MessageTemplateTrigger::CHECKIN_MINUS_1D => $this->bookingRepository->createQueryBuilder('b')
                ->andWhere('b.checkin = :tomorrow')
                ->andWhere('b.status = :status')
                ->setParameter('tomorrow', $now->modify('+1 day')->setTime(0, 0))
                ->setParameter('status', BookingStatus::CONFIRMED)
                ->getQuery()
                ->getResult(),

            MessageTemplateTrigger::CHECKIN_SAME_DAY => $this->bookingRepository->createQueryBuilder('b')
                ->andWhere('b.checkin = :today')
                ->andWhere('b.status = :status')
                ->setParameter('today', $now->setTime(0, 0))
                ->setParameter('status', BookingStatus::CONFIRMED)
                ->getQuery()
                ->getResult(),

            MessageTemplateTrigger::CHECKOUT_PLUS_1D => $this->bookingRepository->createQueryBuilder('b')
                ->andWhere('b.checkout = :yesterday')
                ->andWhere('b.status IN (:statuses)')
                ->setParameter('yesterday', $now->modify('-1 day')->setTime(0, 0))
                ->setParameter('statuses', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
                ->getQuery()
                ->getResult(),

            default => [],
        };
    }

    private function templateMatchesBooking(MessageTemplate $template, Booking $booking): bool
    {
        $templateLodging = $template->getLodging();
        if (null !== $templateLodging) {
            $bookingLodging = $booking->getLodging();
            if (null === $bookingLodging || !$bookingLodging->getId()?->equals($templateLodging->getId())) {
                return false;
            }
        }

        $templateHost = $template->getHostProfile();
        if (null !== $templateHost) {
            $bookingHost = $booking->getLodging()?->getHost();
            if (null === $bookingHost || !$bookingHost->getId()?->equals($templateHost->getId())) {
                return false;
            }
        }

        return true;
    }
}
