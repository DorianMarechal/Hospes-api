<?php

namespace App\Controller;

use App\Dto\DirectBookingRequest;
use App\Dto\DirectBookingResult;
use App\Entity\Booking;
use App\Entity\BookingNight;
use App\Entity\BookingStatusHistory;
use App\Enum\BookingStatus;
use App\Enum\MessageTemplateTrigger;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use App\Repository\PromotionCodeRepository;
use App\Service\AutomatedMessageDispatcher;
use App\Service\AvailabilityResolver;
use App\Service\BookingReferenceGenerator;
use App\Service\OrphanProtectionChecker;
use App\Service\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DirectBookingController
{
    private const TTL_MINUTES = 30;

    public function __construct(
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
        private AvailabilityResolver $availabilityResolver,
        private OrphanProtectionChecker $orphanProtectionChecker,
        private PriceCalculator $priceCalculator,
        private BookingReferenceGenerator $referenceGenerator,
        private PromotionCodeRepository $promotionCodeRepository,
        private AutomatedMessageDispatcher $automatedMessageDispatcher,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/direct-booking', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var DirectBookingRequest $data */
        $data = $this->serializer->deserialize($request->getContent(), DirectBookingRequest::class, 'json');

        $violations = $this->validator->validate($data);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath().': '.$violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], 422);
        }

        $lodging = $this->lodgingRepository->find($data->lodgingId);
        if (null === $lodging || !$lodging->getIsActive()) {
            throw new HttpException(404, 'Lodging not found');
        }

        $checkin = $data->checkin;
        $checkout = $data->checkout;

        if (null === $checkin || null === $checkout || $checkin >= $checkout) {
            throw new HttpException(422, 'Check-in must be before check-out');
        }

        $existingBookings = $this->bookingRepository->findBy(['lodging' => $lodging]);
        $blockedDates = $this->blockedDateRepository->findByLodging($lodging);
        $seasons = $lodging->getSeasons()->toArray();

        $this->availabilityResolver->validateStayDuration($lodging, $checkin, $checkout, $seasons);

        if (!$this->availabilityResolver->isAvailable($lodging, $checkin, $checkout, $existingBookings, $blockedDates, null)) {
            throw new HttpException(409, 'The lodging is not available for the requested dates');
        }

        $this->orphanProtectionChecker->check($lodging, $checkin, $checkout, $existingBookings, $blockedDates, $seasons);

        $quote = $this->priceCalculator->calculate(
            $lodging,
            $checkin,
            $checkout,
            $data->guestsCount ?? 1,
            $seasons,
            $lodging->getPriceOverrides()->toArray(),
        );

        $now = new \DateTimeImmutable();
        $guestPortalToken = bin2hex(random_bytes(32));

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setReference($this->referenceGenerator->generate());
        $booking->setCheckin($checkin);
        $booking->setCheckout($checkout);
        $booking->setGuestsCount($data->guestsCount ?? 1);
        $booking->setNumberOfNights(\count($quote->nights));
        $booking->setNightsTotal($quote->nightsTotal);
        $booking->setCleaningFee($quote->cleaningFee);
        $booking->setTouristTaxTotal($quote->touristTaxTotal);
        $booking->setDepositAmount($quote->depositAmount);
        $booking->setTotalPrice($quote->totalPrice);
        $booking->setCancellationPolicy($lodging->getCancellationPolicy());
        $booking->setCurrency($quote->currency);
        $booking->setGuestEmail($data->guestEmail);
        $booking->setGuestFirstName($data->guestFirstName);
        $booking->setGuestLastName($data->guestLastName);
        $booking->setGuestPhone($data->guestPhone);
        $booking->setGuestPortalToken($guestPortalToken);
        $booking->setSource('direct_widget');

        // Apply promotion code
        if (null !== $data->promotionCode) {
            $promo = $this->promotionCodeRepository->findByCode($data->promotionCode);
            if (null !== $promo && $promo->isUsable()) {
                $promoLodging = $promo->getLodging();
                if (null === $promoLodging || $promoLodging->getId()?->equals($lodging->getId())) {
                    $discount = $promo->calculateDiscount($quote->totalPrice);
                    $booking->setDiscountAmount($discount);
                    $booking->setPromotionCode($promo->getCode());
                    $booking->setTotalPrice($quote->totalPrice - $discount);
                    $promo->incrementUsesCount();
                }
            }
        }

        $booking->setStatus(BookingStatus::PENDING);
        $booking->setExpiresAt($now->modify('+'.self::TTL_MINUTES.' minutes'));
        $booking->setCreatedAt($now);
        $booking->setUpdatedAt($now);

        foreach ($quote->nights as $nightPrice) {
            $night = new BookingNight();
            $night->setDate($nightPrice->date);
            $night->setPrice($nightPrice->price);
            $night->setCurrency($quote->currency);
            $night->setSource($nightPrice->source);
            $booking->addBookingNight($night);
        }

        $history = new BookingStatusHistory();
        $history->setBooking($booking);
        $history->setPreviousStatus(null);
        $history->setNewStatus(BookingStatus::PENDING);
        $history->setCreatedAt($now);

        $this->em->persist($booking);
        $this->em->persist($history);
        $this->em->flush();

        $this->automatedMessageDispatcher->dispatchForBookingEvent($booking, MessageTemplateTrigger::BOOKING_CREATED);

        $result = new DirectBookingResult(
            bookingId: (string) $booking->getId(),
            reference: $booking->getReference() ?? '',
            guestPortalToken: $guestPortalToken,
            stripeCheckoutUrl: null,
            totalPrice: $booking->getTotalPrice() ?? 0,
            currency: $booking->getCurrency(),
            status: $booking->getStatus()->value,
        );

        $json = $this->serializer->serialize($result, 'json', ['groups' => ['direct_booking:read']]);

        return new JsonResponse($json, 201, [], true);
    }
}
