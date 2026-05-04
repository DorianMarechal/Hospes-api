<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Service\AccessCodeManager;
use App\Service\TaskAutoCreator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingCheckOutProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private TaskAutoCreator $taskAutoCreator,
        private AccessCodeManager $accessCodeManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Booking
    {
        if (!$data instanceof Booking) {
            throw new \InvalidArgumentException('Expected '.Booking::class);
        }

        if (BookingStatus::CONFIRMED !== $data->getStatus()) {
            throw new HttpException(422, 'Only confirmed bookings can be checked out');
        }

        if (null === $data->getCheckedInAt()) {
            throw new HttpException(422, 'Guest must check in before checking out');
        }

        if (null !== $data->getCheckedOutAt()) {
            throw new HttpException(422, 'Guest has already checked out');
        }

        $now = new \DateTimeImmutable();
        $data->setCheckedOutAt($now);
        $data->setStatus(BookingStatus::COMPLETED);
        $data->setUpdatedAt($now);

        $this->taskAutoCreator->createCleaningTaskForCheckout($data);
        $this->accessCodeManager->revokeForBooking($data);

        $this->em->flush();

        return $data;
    }
}
