<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\GuestRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingGuestsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private BookingRepository $bookingRepository,
        private GuestRepository $guestRepository,
    ) {
    }

    /**
     * @return \App\Entity\Guest[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $booking = $this->bookingRepository->find($uriVariables['bookingId']);
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $isCustomer = $booking->getCustomer()?->getId()?->equals($user->getId());
        $isHost = $booking->getLodging()?->getHost()?->getUser()?->getId()?->equals($user->getId());

        if (!$isCustomer && !$isHost) {
            throw new AccessDeniedHttpException('You do not have access to this booking.');
        }

        return $this->guestRepository->findByBooking($booking);
    }
}
