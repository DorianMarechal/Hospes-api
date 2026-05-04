<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Guest;
use App\Entity\User;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GuestProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Guest
    {
        if (!$data instanceof Guest) {
            throw new \InvalidArgumentException('Expected '.Guest::class);
        }

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

        $data->setBooking($booking);
        $data->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
