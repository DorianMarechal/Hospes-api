<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\RetainDepositRequest;
use App\Enum\BookingStatus;
use App\Enum\DepositStatus;
use App\Repository\BookingRepository;
use App\Repository\DepositRepository;
use App\Service\DepositManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepositRetainProcessor implements ProcessorInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private DepositRepository $depositRepository,
        private DepositManager $depositManager,
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof RetainDepositRequest);

        $booking = $this->bookingRepository->find($uriVariables['bookingId']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $lodgingHost = $booking->getLodging()?->getHost();
        if (null === $lodgingHost || !$lodgingHost->getId()?->equals($user->getHostProfile()?->getId())) {
            throw new HttpException(403, 'Only the lodging owner can retain a deposit');
        }

        if (BookingStatus::COMPLETED !== $booking->getStatus()) {
            throw new HttpException(422, 'Deposit can only be retained after the stay is completed');
        }

        $deposit = $this->depositRepository->findByBooking($booking);
        if (!$deposit) {
            throw new NotFoundHttpException('No deposit found for this booking');
        }

        if (DepositStatus::HELD !== $deposit->getStatus()) {
            throw new HttpException(422, 'Deposit has already been processed');
        }

        if ($data->retainedAmount > $deposit->getAmount()) {
            throw new HttpException(422, 'Retained amount cannot exceed deposit amount');
        }

        $this->depositManager->retain($deposit, $data->retainedAmount, $data->reason);
        $this->entityManager->flush();

        return $deposit;
    }
}
