<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BookingNight;
use App\Entity\BookingStatusHistory;
use App\Enum\BookingStatus;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Service\AvailabilityResolver;
use App\Service\NotificationDispatcher;
use App\Service\OrphanProtectionChecker;
use App\Service\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingModifyDatesProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
        private AvailabilityResolver $availabilityResolver,
        private OrphanProtectionChecker $orphanProtectionChecker,
        private PriceCalculator $priceCalculator,
        private Security $security,
        private NotificationDispatcher $notificationDispatcher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $booking = $this->bookingRepository->find($uriVariables['id']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        if (!\in_array($booking->getStatus(), [BookingStatus::PENDING, BookingStatus::CONFIRMED])) {
            throw new HttpException(422, 'Only pending or confirmed bookings can be modified');
        }

        $checkin = $data->checkin;
        $checkout = $data->checkout;

        if ($checkin >= $checkout) {
            throw new HttpException(422, 'Check-in must be before check-out');
        }

        $lodging = $booking->getLodging();
        $existingBookings = $this->bookingRepository->findByLodging($lodging);
        $blockedDates = $this->blockedDateRepository->findByLodging($lodging);
        $seasons = $lodging->getSeasons()->toArray();

        $this->availabilityResolver->validateStayDuration($lodging, $checkin, $checkout, $seasons);

        if (!$this->availabilityResolver->isAvailable($lodging, $checkin, $checkout, $existingBookings, $blockedDates, $booking->getId())) {
            throw new HttpException(409, 'The lodging is not available for the requested dates');
        }

        $this->orphanProtectionChecker->check($lodging, $checkin, $checkout, $existingBookings, $blockedDates, $seasons, $booking->getId());

        $quote = $this->priceCalculator->calculate(
            $lodging,
            $checkin,
            $checkout,
            $booking->getGuestsCount(),
            $seasons,
            $lodging->getPriceOverrides()->toArray(),
        );

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $now = new \DateTimeImmutable();

        // Supprimer les anciennes nuits
        foreach ($booking->getBookingNights() as $night) {
            $booking->removeBookingNight($night);
            $this->entityManager->remove($night);
        }

        // Recréer les nuits
        foreach ($quote->nights as $nightPrice) {
            $night = new BookingNight();
            $night->setDate($nightPrice->date);
            $night->setPrice($nightPrice->price);
            $night->setSource($nightPrice->source);
            $booking->addBookingNight($night);
        }

        $booking->setCheckin($checkin);
        $booking->setCheckout($checkout);
        $booking->setNumberOfNights(\count($quote->nights));
        $booking->setNightsTotal($quote->nightsTotal);
        $booking->setCleaningFee($quote->cleaningFee);
        $booking->setTouristTaxTotal($quote->touristTaxTotal);
        $booking->setTotalPrice($quote->totalPrice);
        $booking->setUpdatedAt($now);

        $history = new BookingStatusHistory();
        $history->setBooking($booking);
        $history->setPreviousStatus($booking->getStatus());
        $history->setNewStatus($booking->getStatus());
        $history->setChangedBy($user);
        $history->setReason('Dates modified');
        $history->setCreatedAt($now);

        $this->entityManager->persist($history);
        $this->notificationDispatcher->bookingModified($booking);
        $this->entityManager->flush();

        return $booking;
    }
}
