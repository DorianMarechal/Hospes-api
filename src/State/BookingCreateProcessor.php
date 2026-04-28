<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\BookingRequest;
use App\Entity\Booking;
use App\Entity\BookingNight;
use App\Entity\BookingStatusHistory;
use App\Enum\BookingStatus;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use App\Service\AvailabilityResolver;
use App\Service\BookingReferenceGenerator;
use App\Service\OrphanProtectionChecker;
use App\Service\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingCreateProcessor implements ProcessorInterface
{
    private const TTL_MINUTES = 15;

    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
        private AvailabilityResolver $availabilityResolver,
        private OrphanProtectionChecker $orphanProtectionChecker,
        private PriceCalculator $priceCalculator,
        private BookingReferenceGenerator $referenceGenerator,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof BookingRequest);

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $lodging = $this->lodgingRepository->find($data->lodgingId);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $checkin = $data->checkin;
        $checkout = $data->checkout;

        if ($checkin >= $checkout) {
            throw new HttpException(422, 'Check-in must be before check-out');
        }

        $existingBookings = $this->bookingRepository->findByLodging($lodging);
        $blockedDates = $this->blockedDateRepository->findByLodging($lodging);
        $seasons = $lodging->getSeasons()->toArray();

        // Vérifier la durée de séjour
        $this->availabilityResolver->validateStayDuration($lodging, $checkin, $checkout, $seasons);

        // Vérifier la disponibilité
        if (!$this->availabilityResolver->isAvailable($lodging, $checkin, $checkout, $existingBookings, $blockedDates, null)) {
            throw new HttpException(409, 'The lodging is not available for the requested dates');
        }

        // Vérifier les nuits orphelines
        $this->orphanProtectionChecker->check($lodging, $checkin, $checkout, $existingBookings, $blockedDates, $seasons);

        // Calculer le prix
        $quote = $this->priceCalculator->calculate(
            $lodging,
            $checkin,
            $checkout,
            $data->guestsCount,
            $seasons,
            $lodging->getPriceOverrides()->toArray(),
        );

        $now = new \DateTimeImmutable();

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setCustomer($user);
        $booking->setReference($this->referenceGenerator->generate());
        $booking->setCheckin($checkin);
        $booking->setCheckout($checkout);
        $booking->setGuestsCount($data->guestsCount);
        $booking->setNumberOfNights(\count($quote->nights));
        $booking->setNightsTotal($quote->nightsTotal);
        $booking->setCleaningFee($quote->cleaningFee);
        $booking->setTouristTaxTotal($quote->touristTaxTotal);
        $booking->setDepositAmount($quote->depositAmount);
        $booking->setTotalPrice($quote->totalPrice);
        $booking->setCancellationPolicy($lodging->getCancellationPolicy());
        $booking->setStatus(BookingStatus::PENDING);
        $booking->setExpiresAt($now->modify('+'.self::TTL_MINUTES.' minutes'));
        $booking->setCreatedAt($now);
        $booking->setUpdatedAt($now);

        foreach ($quote->nights as $nightPrice) {
            $night = new BookingNight();
            $night->setDate($nightPrice->date);
            $night->setPrice($nightPrice->price);
            $night->setSource($nightPrice->source);
            $booking->addBookingNight($night);
        }

        $history = new BookingStatusHistory();
        $history->setBooking($booking);
        $history->setPreviousStatus(null);
        $history->setNewStatus(BookingStatus::PENDING);
        $history->setChangedBy($user);
        $history->setCreatedAt($now);

        $this->entityManager->persist($booking);
        $this->entityManager->persist($history);
        $this->entityManager->flush();

        return $booking;
    }
}
