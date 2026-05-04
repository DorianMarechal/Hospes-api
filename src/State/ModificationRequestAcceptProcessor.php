<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BookingModificationRequest;
use App\Entity\BookingNight;
use App\Entity\BookingStatusHistory;
use App\Enum\ModificationRequestStatus;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Service\AvailabilityResolver;
use App\Service\NotificationDispatcher;
use App\Service\OrphanProtectionChecker;
use App\Service\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ModificationRequestAcceptProcessor implements ProcessorInterface
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
        if (!$data instanceof BookingModificationRequest) {
            throw new \InvalidArgumentException('Expected '.BookingModificationRequest::class);
        }

        if (ModificationRequestStatus::PENDING !== $data->getStatus()) {
            throw new HttpException(422, 'Only pending modification requests can be accepted');
        }

        if ($data->getExpiresAt() <= new \DateTimeImmutable()) {
            $data->setStatus(ModificationRequestStatus::EXPIRED);
            $this->notificationDispatcher->modificationExpired($data);
            $this->entityManager->flush();
            $this->notificationDispatcher->publishPendingNotifications();

            throw new HttpException(422, 'This modification request has expired');
        }

        $booking = $data->getBooking();
        if (null === $booking) {
            throw new HttpException(422, 'Modification request has no associated booking');
        }
        $lodging = $booking->getLodging();
        if (null === $lodging) {
            throw new HttpException(422, 'Booking has no associated lodging');
        }
        $checkin = $data->getProposedCheckin();
        $checkout = $data->getProposedCheckout();

        // Re-vérifier la disponibilité au moment de l'acceptation
        $existingBookings = $this->bookingRepository->findByLodging($lodging);
        $blockedDates = $this->blockedDateRepository->findByLodging($lodging);
        $seasons = $lodging->getSeasons()->toArray();

        if (!$this->availabilityResolver->isAvailable($lodging, $checkin, $checkout, $existingBookings, $blockedDates, $booking->getId())) {
            throw new HttpException(409, 'The lodging is no longer available for the requested dates');
        }

        $this->orphanProtectionChecker->check($lodging, $checkin, $checkout, $existingBookings, $blockedDates, $seasons, $booking->getId());

        // Recalculer le prix (les tarifs ont pu changer entre la proposition et l'acceptation)
        $quote = $this->priceCalculator->calculate(
            $lodging,
            $checkin,
            $checkout,
            $booking->getGuestsCount() ?? 1,
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
        $history->setReason('Modification request accepted');
        $history->setCreatedAt($now);

        $data->setStatus(ModificationRequestStatus::ACCEPTED);
        $data->setRespondedAt($now);

        $this->entityManager->persist($history);
        $this->notificationDispatcher->modificationAccepted($data);
        $this->entityManager->flush();
        $this->notificationDispatcher->publishPendingNotifications();

        return $data;
    }
}
