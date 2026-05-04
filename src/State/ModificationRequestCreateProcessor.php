<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BookingModificationRequest;
use App\Enum\BookingStatus;
use App\Enum\ModificationRequestStatus;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingModificationRequestRepository;
use App\Repository\BookingRepository;
use App\Service\AvailabilityResolver;
use App\Service\NotificationDispatcher;
use App\Service\OrphanProtectionChecker;
use App\Service\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModificationRequestCreateProcessor implements ProcessorInterface
{
    private const TTL_HOURS = 48;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingRepository $bookingRepository,
        private BookingModificationRequestRepository $modificationRequestRepository,
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
        $booking = $this->bookingRepository->find($uriVariables['bookingId']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        // Vérifier que l'utilisateur est le customer ou l'hôte
        $isCustomer = null !== $booking->getCustomer()?->getId()
            && $booking->getCustomer()->getId()->equals($user->getId());
        $isHost = null !== $booking->getLodging()?->getHost()?->getId()
            && $booking->getLodging()->getHost()->getId()->equals($user->getHostProfile()?->getId());

        if (!$isCustomer && !$isHost && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Access denied');
        }

        if (!\in_array($booking->getStatus(), [BookingStatus::PENDING, BookingStatus::CONFIRMED])) {
            throw new HttpException(422, 'Only pending or confirmed bookings can be modified');
        }

        // Vérifier qu'il n'y a pas déjà une demande en cours
        $existingRequest = $this->modificationRequestRepository->findPendingByBooking($booking);
        if (null !== $existingRequest) {
            // Expirer si TTL dépassé
            if ($existingRequest->getExpiresAt() <= new \DateTimeImmutable()) {
                $existingRequest->setStatus(ModificationRequestStatus::EXPIRED);
                $this->notificationDispatcher->modificationExpired($existingRequest);
            } else {
                throw new HttpException(409, 'A pending modification request already exists for this booking');
            }
        }

        $checkin = $data->checkin;
        $checkout = $data->checkout;

        if ($checkin >= $checkout) {
            throw new HttpException(422, 'Check-in must be before check-out');
        }

        $lodging = $booking->getLodging();
        if (null === $lodging) {
            throw new HttpException(422, 'Booking has no associated lodging');
        }
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
            $booking->getGuestsCount() ?? 1,
            $seasons,
            $lodging->getPriceOverrides()->toArray(),
        );

        $now = new \DateTimeImmutable();

        $modificationRequest = new BookingModificationRequest();
        $modificationRequest->setBooking($booking);
        $modificationRequest->setRequestedBy($user);
        $modificationRequest->setProposedCheckin($checkin);
        $modificationRequest->setProposedCheckout($checkout);
        $modificationRequest->setProposedNumberOfNights(\count($quote->nights));
        $modificationRequest->setProposedNightsTotal($quote->nightsTotal);
        $modificationRequest->setProposedCleaningFee($quote->cleaningFee);
        $modificationRequest->setProposedTouristTaxTotal($quote->touristTaxTotal);
        $modificationRequest->setProposedDepositAmount($quote->depositAmount);
        $modificationRequest->setProposedTotalPrice($quote->totalPrice);
        $modificationRequest->setStatus(ModificationRequestStatus::PENDING);
        $modificationRequest->setExpiresAt($now->modify('+'.self::TTL_HOURS.' hours'));
        $modificationRequest->setCreatedAt($now);

        $this->entityManager->persist($modificationRequest);
        $this->notificationDispatcher->modificationRequested($modificationRequest);
        $this->entityManager->flush();
        $this->notificationDispatcher->publishPendingNotifications();

        return $modificationRequest;
    }
}
