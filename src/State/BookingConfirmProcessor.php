<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Booking;
use App\Entity\BookingStatusHistory;
use App\Enum\BookingStatus;
use App\Enum\MessageTemplateTrigger;
use App\Service\AutomatedMessageDispatcher;
use App\Service\DepositManager;
use App\Service\NotificationDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingConfirmProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private NotificationDispatcher $notificationDispatcher,
        private DepositManager $depositManager,
        private AutomatedMessageDispatcher $automatedMessageDispatcher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Booking) {
            throw new \InvalidArgumentException('Expected '.Booking::class);
        }

        if (BookingStatus::PENDING !== $data->getStatus()) {
            throw new HttpException(422, 'Only pending bookings can be confirmed');
        }

        $expiresAt = $data->getExpiresAt();
        if (null !== $expiresAt && $expiresAt < new \DateTimeImmutable()) {
            $now = new \DateTimeImmutable();
            $data->setStatus(BookingStatus::CANCELLED);
            $data->setUpdatedAt($now);

            $expireHistory = new BookingStatusHistory();
            $expireHistory->setBooking($data);
            $expireHistory->setPreviousStatus(BookingStatus::PENDING);
            $expireHistory->setNewStatus(BookingStatus::CANCELLED);
            $expireHistory->setReason('Booking expired (TTL exceeded)');
            $expireHistory->setCreatedAt($now);
            $this->entityManager->persist($expireHistory);

            $this->entityManager->flush();
            throw new HttpException(410, 'This booking has expired');
        }

        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw new HttpException(401, 'Authentication required');
        }
        $now = new \DateTimeImmutable();

        $history = new BookingStatusHistory();
        $history->setBooking($data);
        $history->setPreviousStatus(BookingStatus::PENDING);
        $history->setNewStatus(BookingStatus::CONFIRMED);
        $history->setChangedBy($user);
        $history->setCreatedAt($now);

        $data->setStatus(BookingStatus::CONFIRMED);
        $data->setExpiresAt(null);
        $data->setUpdatedAt($now);

        $this->entityManager->persist($history);

        $this->depositManager->createFromBooking($data);
        $this->notificationDispatcher->bookingConfirmed($data);

        $this->entityManager->flush();
        $this->notificationDispatcher->publishPendingNotifications();
        $this->automatedMessageDispatcher->dispatchForBookingEvent($data, MessageTemplateTrigger::BOOKING_CONFIRMED);

        return $data;
    }
}
