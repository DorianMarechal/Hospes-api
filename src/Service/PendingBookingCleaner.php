<?php

namespace App\Service;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Message\SendNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PendingBookingCleaner
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function cleanExpired(): int
    {
        $now = new \DateTimeImmutable();

        // Find bookings to expire (for notification dispatch)
        $expiring = $this->em->createQueryBuilder()
            ->select('b')
            ->from(Booking::class, 'b')
            ->where('b.status = :pending')
            ->andWhere('b.expiresAt < :now')
            ->setParameter('pending', BookingStatus::PENDING)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($expiring as $booking) {
            $customerId = $booking->getCustomer()?->getId()?->toRfc4122();
            if (null !== $customerId) {
                $this->messageBus->dispatch(new SendNotificationMessage(
                    $customerId,
                    'booking_expired',
                    'Réservation expirée',
                    sprintf(
                        'Votre réservation %s pour "%s" a expiré (non confirmée dans les 15 minutes).',
                        $booking->getReference(),
                        $booking->getLodging()?->getName() ?? '',
                    ),
                ));
            }
        }

        // Bulk update
        $qb = $this->em->createQueryBuilder();
        $qb->update(Booking::class, 'b')
            ->set('b.status', ':cancelled')
            ->set('b.updatedAt', ':now')
            ->where('b.status = :pending')
            ->andWhere('b.expiresAt < :now')
            ->setParameter('cancelled', BookingStatus::CANCELLED)
            ->setParameter('pending', BookingStatus::PENDING)
            ->setParameter('now', $now);

        return $qb->getQuery()->execute();
    }
}
