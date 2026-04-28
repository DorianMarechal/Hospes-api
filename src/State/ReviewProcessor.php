<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReviewProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private BookingRepository $bookingRepository,
        private ReviewRepository $reviewRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Review
    {
        assert($data instanceof Review);

        /** @var User $user */
        $user = $this->security->getUser();

        $booking = $this->bookingRepository->find($uriVariables['bookingId']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        if (true !== $booking->getCustomer()?->getId()?->equals($user->getId())) {
            throw new BadRequestHttpException('You can only review your own bookings');
        }

        if (BookingStatus::COMPLETED !== $booking->getStatus()) {
            throw new BadRequestHttpException('You can only review completed bookings');
        }

        $existing = $this->reviewRepository->findOneBy(['booking' => $booking]);
        if ($existing) {
            throw new BadRequestHttpException('A review already exists for this booking');
        }

        $now = new \DateTimeImmutable();

        $data->setBooking($booking);
        $data->setLodging($booking->getLodging());
        $data->setCustomer($user);
        $data->setCreatedAt($now);
        $data->setUpdatedAt($now);

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
