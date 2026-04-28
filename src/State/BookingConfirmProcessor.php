<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Booking;
use App\Entity\BookingStatusHistory;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingConfirmProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof Booking);

        if (BookingStatus::PENDING !== $data->getStatus()) {
            throw new HttpException(422, 'Only pending bookings can be confirmed');
        }

        if ($data->getExpiresAt() < new \DateTimeImmutable()) {
            $data->setStatus(BookingStatus::CANCELLED);
            $this->entityManager->flush();
            throw new HttpException(410, 'This booking has expired');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
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
        $this->entityManager->flush();

        return $data;
    }
}
