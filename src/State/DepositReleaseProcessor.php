<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Enum\BookingStatus;
use App\Enum\DepositStatus;
use App\Repository\BookingRepository;
use App\Repository\DepositRepository;
use App\Service\DepositManager;
use App\Service\NotificationDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepositReleaseProcessor implements ProcessorInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private DepositRepository $depositRepository,
        private DepositManager $depositManager,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private NotificationDispatcher $notificationDispatcher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $booking = $this->bookingRepository->find($uriVariables['bookingId']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $lodgingHost = $booking->getLodging()?->getHost();
        if (null === $lodgingHost || !$lodgingHost->getId()?->equals($user->getHostProfile()?->getId())) {
            throw new HttpException(403, 'Only the lodging owner can release a deposit');
        }

        if (BookingStatus::COMPLETED !== $booking->getStatus()) {
            throw new HttpException(422, 'Deposit can only be released after the stay is completed');
        }

        $deposit = $this->depositRepository->findByBooking($booking);
        if (!$deposit) {
            throw new NotFoundHttpException('No deposit found for this booking');
        }

        if (DepositStatus::HELD !== $deposit->getStatus()) {
            throw new HttpException(422, 'Deposit has already been processed');
        }

        $this->depositManager->release($deposit);
        $this->notificationDispatcher->depositReleased($deposit);
        $this->entityManager->flush();

        return $deposit;
    }
}
