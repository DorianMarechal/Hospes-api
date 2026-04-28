<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BookingStatusHistory;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingCancelProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingRepository $bookingRepository,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $booking = $this->bookingRepository->find($uriVariables['id']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        $status = $booking->getStatus();
        if (!\in_array($status, [BookingStatus::PENDING, BookingStatus::CONFIRMED])) {
            throw new HttpException(422, 'Only pending or confirmed bookings can be cancelled');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $now = new \DateTimeImmutable();

        $history = new BookingStatusHistory();
        $history->setBooking($booking);
        $history->setPreviousStatus($status);
        $history->setNewStatus(BookingStatus::CANCELLED);
        $history->setChangedBy($user);
        $history->setReason($data->reason ?? null);
        $history->setCreatedAt($now);

        $booking->setStatus(BookingStatus::CANCELLED);
        $booking->setCancelledBy($user);
        $booking->setCancellationReason($data->reason ?? null);
        $booking->setUpdatedAt($now);

        $this->entityManager->persist($history);
        $this->entityManager->flush();

        return $booking;
    }
}
