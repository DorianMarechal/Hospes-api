<?php

namespace App\MessageHandler;

use App\Entity\AutomatedMessageLog;
use App\Enum\MessageChannel;
use App\Message\DispatchAutomatedMessageMessage;
use App\Repository\AutomatedMessageLogRepository;
use App\Repository\BookingRepository;
use App\Repository\MessageTemplateRepository;
use App\Service\EmailSender;
use App\Service\NotificationDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DispatchAutomatedMessageHandler
{
    public function __construct(
        private MessageTemplateRepository $templateRepository,
        private BookingRepository $bookingRepository,
        private AutomatedMessageLogRepository $logRepository,
        private EntityManagerInterface $entityManager,
        private EmailSender $emailSender,
        private NotificationDispatcher $notificationDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DispatchAutomatedMessageMessage $message): void
    {
        $template = $this->templateRepository->find($message->templateId);
        $booking = $this->bookingRepository->find($message->bookingId);

        if (null === $template || null === $booking) {
            return;
        }

        if (!$template->isEnabled()) {
            return;
        }

        $customer = $booking->getCustomer();
        if (null === $customer) {
            return;
        }

        $lodging = $booking->getLodging();
        $variables = [
            '{guest_name}' => $customer->getFirstName().' '.$customer->getLastName(),
            '{lodging_name}' => $lodging?->getName() ?? '',
            '{checkin_date}' => $booking->getCheckin()?->format('d/m/Y') ?? '',
            '{checkout_date}' => $booking->getCheckout()?->format('d/m/Y') ?? '',
            '{reference}' => $booking->getReference() ?? '',
            '{checkin_time}' => $lodging?->getCheckinTime()?->format('H:i') ?? '',
            '{address}' => $lodging?->getAddress() ?? '',
        ];

        $subject = str_replace(array_keys($variables), array_values($variables), $template->getSubject());
        $body = str_replace(array_keys($variables), array_values($variables), $template->getBody());

        foreach ($template->getChannels() as $channelValue) {
            $channel = MessageChannel::tryFrom($channelValue);
            if (null === $channel) {
                continue;
            }

            if ($this->logRepository->hasAlreadySent($template, $booking, $channel)) {
                continue;
            }

            match ($channel) {
                MessageChannel::EMAIL => $this->emailSender->sendAutomatedMessage(
                    $customer->getEmail(),
                    $subject,
                    $body,
                ),
                MessageChannel::IN_APP => $this->notificationDispatcher->automatedMessage(
                    $customer,
                    $subject,
                    $body,
                    $booking,
                ),
                MessageChannel::SMS => $this->logger->info('SMS channel not implemented', [
                    'booking' => $booking->getReference(),
                ]),
            };

            $log = new AutomatedMessageLog();
            $log->setMessageTemplate($template);
            $log->setBooking($booking);
            $log->setTriggerType($template->getTriggerType());
            $log->setChannel($channel);
            $log->setSentAt(new \DateTimeImmutable());
            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();
        $this->notificationDispatcher->publishPendingNotifications();
    }
}
