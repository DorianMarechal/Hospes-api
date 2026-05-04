<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\BookingModificationRequest;
use App\Entity\Deposit;
use App\Entity\Notification;
use App\Entity\Payment;
use App\Entity\Review;
use App\Entity\StaffAssignment;
use App\Entity\User;
use App\Enum\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class NotificationDispatcher
{
    /** @var Notification[] */
    private array $pendingNotifications = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MercurePublisher $mercurePublisher,
    ) {
    }

    public function publishPendingNotifications(): void
    {
        foreach ($this->pendingNotifications as $notification) {
            $this->mercurePublisher->publishNotification($notification);
        }
        $this->pendingNotifications = [];
    }

    public function bookingConfirmed(Booking $booking): void
    {
        $host = $booking->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $this->create(
            $host,
            NotificationType::BOOKING_CONFIRMED,
            'Réservation confirmée',
            \sprintf('La réservation %s pour "%s" a été confirmée.', $booking->getReference(), $booking->getLodging()?->getName() ?? ''),
            'booking',
            $booking->getId(),
            [
                'reference' => $booking->getReference() ?? '',
                'lodging_name' => $booking->getLodging()?->getName() ?? '',
            ],
        );
    }

    public function bookingCancelled(Booking $booking): void
    {
        $customer = $booking->getCustomer();
        $host = $booking->getLodging()?->getHost()?->getUser();
        $cancelledBy = $booking->getCancelledBy();
        $lodgingName = $booking->getLodging()?->getName() ?? '';
        $reference = $booking->getReference() ?? '';
        $params = ['reference' => $reference, 'lodging_name' => $lodgingName];

        if (null !== $customer && (null === $cancelledBy || !$cancelledBy->getId()?->equals($customer->getId()))) {
            $this->create(
                $customer,
                NotificationType::BOOKING_CANCELLED,
                'Réservation annulée',
                \sprintf('Votre réservation %s pour "%s" a été annulée.', $reference, $lodgingName),
                'booking',
                $booking->getId(),
                $params,
            );
        }

        if (null !== $host && (null === $cancelledBy || !$cancelledBy->getId()?->equals($host->getId()))) {
            $this->create(
                $host,
                NotificationType::BOOKING_CANCELLED,
                'Réservation annulée',
                \sprintf('La réservation %s pour "%s" a été annulée.', $reference, $lodgingName),
                'booking',
                $booking->getId(),
                $params,
            );
        }
    }

    public function bookingModified(Booking $booking): void
    {
        $host = $booking->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $this->create(
            $host,
            NotificationType::BOOKING_MODIFIED,
            'Réservation modifiée',
            \sprintf('La réservation %s pour "%s" a été modifiée.', $booking->getReference(), $booking->getLodging()?->getName() ?? ''),
            'booking',
            $booking->getId(),
            [
                'reference' => $booking->getReference() ?? '',
                'lodging_name' => $booking->getLodging()?->getName() ?? '',
            ],
        );
    }

    public function bookingExpired(Booking $booking): void
    {
        $customer = $booking->getCustomer();
        if (null === $customer) {
            return;
        }

        $this->create(
            $customer,
            NotificationType::BOOKING_EXPIRED,
            'Réservation expirée',
            \sprintf('Votre réservation %s pour "%s" a expiré (non confirmée dans les 15 minutes).', $booking->getReference(), $booking->getLodging()?->getName() ?? ''),
            'booking',
            $booking->getId(),
            [
                'reference' => $booking->getReference() ?? '',
                'lodging_name' => $booking->getLodging()?->getName() ?? '',
            ],
        );
    }

    public function staffInvited(StaffAssignment $assignment, string $email): void
    {
        $host = $assignment->getHost();
        if (null === $host) {
            return;
        }

        $this->create(
            $host,
            NotificationType::STAFF_INVITED,
            'Invitation envoyée',
            \sprintf('Une invitation a été envoyée à %s.', $email),
            'staff_assignment',
            $assignment->getId(),
            ['email' => $email],
        );
    }

    public function reviewReceived(Review $review): void
    {
        $host = $review->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $this->create(
            $host,
            NotificationType::REVIEW_RECEIVED,
            'Nouvel avis',
            \sprintf('Un avis a été laissé sur "%s" (%d/5).', $review->getLodging()?->getName() ?? '', $review->getRating()),
            'review',
            $review->getId(),
            [
                'lodging_name' => $review->getLodging()?->getName() ?? '',
                'rating' => (string) $review->getRating(),
            ],
        );
    }

    public function messageReceived(User $recipient, string $lodgingName): void
    {
        $this->create(
            $recipient,
            NotificationType::MESSAGE_RECEIVED,
            'Nouveau message',
            \sprintf('Vous avez reçu un nouveau message concernant "%s".', $lodgingName),
            'conversation',
            null,
            ['lodging_name' => $lodgingName],
        );
    }

    public function paymentReceived(Payment $payment): void
    {
        $host = $payment->getBooking()?->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $reference = $payment->getBooking()?->getReference() ?? '';
        $amountCents = $payment->getAmount() ?? 0;

        $this->create(
            $host,
            NotificationType::PAYMENT_RECEIVED,
            'Paiement reçu',
            \sprintf('Un paiement de %s € a été reçu pour la réservation %s.', number_format($amountCents / 100, 2, ',', ' '), $reference),
            'payment',
            $payment->getId(),
            [
                'reference' => $reference,
                'amount' => (string) $amountCents,
                'currency' => 'EUR',
            ],
        );
    }

    public function modificationRequested(BookingModificationRequest $request): void
    {
        $booking = $request->getBooking();
        $requestedBy = $request->getRequestedBy();
        if (null === $booking || null === $requestedBy) {
            return;
        }

        $lodgingName = $booking->getLodging()?->getName() ?? '';
        $reference = $booking->getReference() ?? '';
        $params = ['reference' => $reference, 'lodging_name' => $lodgingName];
        $customer = $booking->getCustomer();
        $host = $booking->getLodging()?->getHost()?->getUser();

        if (null !== $customer && !$requestedBy->getId()?->equals($customer->getId())) {
            $this->create(
                $customer,
                NotificationType::MODIFICATION_REQUESTED,
                'Modification proposée',
                \sprintf('Une modification a été proposée pour votre réservation %s ("%s").', $reference, $lodgingName),
                'booking_modification_request',
                $request->getId(),
                $params,
            );
        }

        if (null !== $host && !$requestedBy->getId()?->equals($host->getId())) {
            $this->create(
                $host,
                NotificationType::MODIFICATION_REQUESTED,
                'Modification proposée',
                \sprintf('Une modification a été proposée pour la réservation %s ("%s").', $reference, $lodgingName),
                'booking_modification_request',
                $request->getId(),
                $params,
            );
        }
    }

    public function modificationAccepted(BookingModificationRequest $request): void
    {
        $booking = $request->getBooking();
        $requestedBy = $request->getRequestedBy();
        if (null === $booking || null === $requestedBy) {
            return;
        }

        $lodgingName = $booking->getLodging()?->getName() ?? '';
        $reference = $booking->getReference() ?? '';

        $this->create(
            $requestedBy,
            NotificationType::MODIFICATION_ACCEPTED,
            'Modification acceptée',
            \sprintf('Votre proposition de modification pour la réservation %s ("%s") a été acceptée.', $reference, $lodgingName),
            'booking_modification_request',
            $request->getId(),
            ['reference' => $reference, 'lodging_name' => $lodgingName],
        );
    }

    public function modificationRejected(BookingModificationRequest $request): void
    {
        $booking = $request->getBooking();
        $requestedBy = $request->getRequestedBy();
        if (null === $booking || null === $requestedBy) {
            return;
        }

        $lodgingName = $booking->getLodging()?->getName() ?? '';
        $reference = $booking->getReference() ?? '';

        $this->create(
            $requestedBy,
            NotificationType::MODIFICATION_REJECTED,
            'Modification refusée',
            \sprintf('Votre proposition de modification pour la réservation %s ("%s") a été refusée.', $reference, $lodgingName),
            'booking_modification_request',
            $request->getId(),
            ['reference' => $reference, 'lodging_name' => $lodgingName],
        );
    }

    public function modificationExpired(BookingModificationRequest $request): void
    {
        $booking = $request->getBooking();
        $requestedBy = $request->getRequestedBy();
        if (null === $booking || null === $requestedBy) {
            return;
        }

        $lodgingName = $booking->getLodging()?->getName() ?? '';
        $reference = $booking->getReference() ?? '';
        $params = ['reference' => $reference, 'lodging_name' => $lodgingName];

        $customer = $booking->getCustomer();
        $host = $booking->getLodging()?->getHost()?->getUser();

        if (null !== $customer) {
            $this->create(
                $customer,
                NotificationType::MODIFICATION_EXPIRED,
                'Modification expirée',
                \sprintf('La proposition de modification pour la réservation %s ("%s") a expiré.', $reference, $lodgingName),
                'booking_modification_request',
                $request->getId(),
                $params,
            );
        }

        if (null !== $host) {
            $this->create(
                $host,
                NotificationType::MODIFICATION_EXPIRED,
                'Modification expirée',
                \sprintf('La proposition de modification pour la réservation %s ("%s") a expiré.', $reference, $lodgingName),
                'booking_modification_request',
                $request->getId(),
                $params,
            );
        }
    }

    public function taskAssigned(User $assignee, string $taskType, string $lodgingName, ?Uuid $taskId): void
    {
        $this->create(
            $assignee,
            NotificationType::TASK_ASSIGNED,
            'Tâche assignée',
            \sprintf('Une tâche "%s" vous a été assignée pour "%s".', $taskType, $lodgingName),
            'task',
            $taskId,
            ['task_type' => $taskType, 'lodging_name' => $lodgingName],
        );
    }

    public function automatedMessage(User $user, string $title, string $content, Booking $booking): void
    {
        $this->create(
            $user,
            NotificationType::AUTOMATED_MESSAGE,
            $title,
            $content,
            'booking',
            $booking->getId(),
            [
                'reference' => $booking->getReference() ?? '',
                'lodging_name' => $booking->getLodging()?->getName() ?? '',
            ],
        );
    }

    public function depositReleased(Deposit $deposit): void
    {
        $customer = $deposit->getBooking()?->getCustomer();
        if (null === $customer) {
            return;
        }

        $reference = $deposit->getBooking()?->getReference() ?? '';

        $this->create(
            $customer,
            NotificationType::DEPOSIT_RELEASED,
            'Caution libérée',
            \sprintf('La caution de votre réservation %s a été libérée.', $reference),
            'booking',
            $deposit->getBooking()?->getId(),
            ['reference' => $reference],
        );
    }

    /**
     * @param array<string, string>|null $params
     */
    private function create(
        User $user,
        NotificationType $type,
        string $title,
        string $content,
        ?string $relatedEntityType,
        ?Uuid $relatedEntityId,
        ?array $params = null,
    ): void {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setContent($content);
        $notification->setRelatedEntityType($relatedEntityType);
        $notification->setRelatedEntityId($relatedEntityId);
        $notification->setParams($params);
        $notification->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);
        $this->pendingNotifications[] = $notification;
    }
}
