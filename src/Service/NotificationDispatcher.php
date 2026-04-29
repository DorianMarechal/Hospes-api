<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Deposit;
use App\Entity\Notification;
use App\Entity\Payment;
use App\Entity\Review;
use App\Entity\StaffAssignment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class NotificationDispatcher
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function bookingConfirmed(Booking $booking): void
    {
        $host = $booking->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $lodgingName = $booking->getLodging()?->getName() ?? '';

        $this->create(
            $host,
            'booking_confirmed',
            'Réservation confirmée',
            \sprintf('La réservation %s pour "%s" a été confirmée.', $booking->getReference(), $lodgingName),
            'booking',
            $booking->getId(),
        );
    }

    public function bookingCancelled(Booking $booking): void
    {
        $customer = $booking->getCustomer();
        $host = $booking->getLodging()?->getHost()?->getUser();
        $cancelledBy = $booking->getCancelledBy();
        $lodgingName = $booking->getLodging()?->getName() ?? '';

        // Notify the party that did NOT cancel
        if (null !== $customer && (null === $cancelledBy || !$cancelledBy->getId()?->equals($customer->getId()))) {
            $this->create(
                $customer,
                'booking_cancelled',
                'Réservation annulée',
                \sprintf('Votre réservation %s pour "%s" a été annulée.', $booking->getReference(), $lodgingName),
                'booking',
                $booking->getId(),
            );
        }

        if (null !== $host && (null === $cancelledBy || !$cancelledBy->getId()?->equals($host->getId()))) {
            $this->create(
                $host,
                'booking_cancelled',
                'Réservation annulée',
                \sprintf('La réservation %s pour "%s" a été annulée.', $booking->getReference(), $lodgingName),
                'booking',
                $booking->getId(),
            );
        }
    }

    public function bookingModified(Booking $booking): void
    {
        $host = $booking->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $lodgingName = $booking->getLodging()?->getName() ?? '';

        $this->create(
            $host,
            'booking_modified',
            'Réservation modifiée',
            \sprintf('La réservation %s pour "%s" a été modifiée.', $booking->getReference(), $lodgingName),
            'booking',
            $booking->getId(),
        );
    }

    public function bookingExpired(Booking $booking): void
    {
        $customer = $booking->getCustomer();
        if (null === $customer) {
            return;
        }

        $lodgingName = $booking->getLodging()?->getName() ?? '';

        $this->create(
            $customer,
            'booking_expired',
            'Réservation expirée',
            \sprintf('Votre réservation %s pour "%s" a expiré (non confirmée dans les 15 minutes).', $booking->getReference(), $lodgingName),
            'booking',
            $booking->getId(),
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
            'staff_invited',
            'Invitation envoyée',
            \sprintf('Une invitation a été envoyée à %s.', $email),
            'staff_assignment',
            $assignment->getId(),
        );
    }

    public function reviewReceived(Review $review): void
    {
        $host = $review->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $lodgingName = $review->getLodging()?->getName() ?? '';

        $this->create(
            $host,
            'review_received',
            'Nouvel avis',
            \sprintf('Un avis a été laissé sur "%s" (%d/5).', $lodgingName, $review->getRating()),
            'review',
            $review->getId(),
        );
    }

    public function messageReceived(User $recipient, string $lodgingName): void
    {
        $this->create(
            $recipient,
            'message_received',
            'Nouveau message',
            \sprintf('Vous avez reçu un nouveau message concernant "%s".', $lodgingName),
            'conversation',
            null,
        );
    }

    public function paymentReceived(Payment $payment): void
    {
        $host = $payment->getBooking()?->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $reference = $payment->getBooking()?->getReference() ?? '';
        $amountEuros = number_format(($payment->getAmount() ?? 0) / 100, 2, ',', ' ');

        $this->create(
            $host,
            'payment_received',
            'Paiement reçu',
            \sprintf('Un paiement de %s € a été reçu pour la réservation %s.', $amountEuros, $reference),
            'payment',
            $payment->getId(),
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
            'deposit_released',
            'Caution libérée',
            \sprintf('La caution de votre réservation %s a été libérée.', $reference),
            'booking',
            $deposit->getBooking()?->getId(),
        );
    }

    private function create(
        User $user,
        string $type,
        string $title,
        string $content,
        ?string $relatedEntityType,
        ?Uuid $relatedEntityId,
    ): void {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setContent($content);
        $notification->setRelatedEntityType($relatedEntityType);
        $notification->setRelatedEntityId($relatedEntityId);
        $notification->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);
    }
}
