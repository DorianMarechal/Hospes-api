<?php

namespace App\Controller;

use App\Repository\AccessCodeRepository;
use App\Repository\BookingRepository;
use App\Repository\ExtraRepository;
use App\Repository\GuestRepository;
use App\Repository\GuidebookRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class GuestPortalController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private GuestRepository $guestRepository,
        private AccessCodeRepository $accessCodeRepository,
        private GuidebookRepository $guidebookRepository,
        private ExtraRepository $extraRepository,
    ) {
    }

    #[Route('/api/guest-portal/{token}', methods: ['GET'])]
    public function portal(string $token): JsonResponse
    {
        $booking = $this->bookingRepository->findOneBy(['guestPortalToken' => $token]);
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        $lodging = $booking->getLodging();
        $guests = $this->guestRepository->findByBooking($booking);
        $accessCode = $this->accessCodeRepository->findByBooking($booking);
        $guidebook = null !== $lodging ? $this->guidebookRepository->findByLodging($lodging) : null;
        $extras = null !== $lodging ? $this->extraRepository->findEnabledByLodging($lodging) : [];

        return new JsonResponse([
            'booking' => [
                'reference' => $booking->getReference(),
                'status' => $booking->getStatus()->value,
                'checkin' => $booking->getCheckin()?->format('Y-m-d'),
                'checkout' => $booking->getCheckout()?->format('Y-m-d'),
                'guestsCount' => $booking->getGuestsCount(),
                'totalPrice' => $booking->getTotalPrice(),
                'currency' => $booking->getCurrency(),
                'guestFirstName' => $booking->getGuestFirstName() ?? $booking->getCustomer()?->getFirstName(),
                'guestLastName' => $booking->getGuestLastName() ?? $booking->getCustomer()?->getLastName(),
            ],
            'lodging' => null !== $lodging ? [
                'name' => $lodging->getName(),
                'address' => $lodging->getAddress(),
                'city' => $lodging->getCity(),
                'postalCode' => $lodging->getPostalCode(),
                'checkinTime' => $lodging->getCheckinTime()?->format('H:i'),
                'checkoutTime' => $lodging->getCheckoutTime()?->format('H:i'),
            ] : null,
            'accessCode' => null !== $accessCode && !$accessCode->isRevoked() ? [
                'code' => $accessCode->getCode(),
                'validFrom' => $accessCode->getValidFrom()?->format('Y-m-d H:i'),
                'validTo' => $accessCode->getValidTo()?->format('Y-m-d H:i'),
            ] : null,
            'guidebook' => null !== $guidebook ? [
                'checkinInstructions' => $guidebook->getCheckinInstructions(),
                'houseRules' => $guidebook->getHouseRules(),
                'wifiName' => $guidebook->getWifiName(),
                'wifiPassword' => $guidebook->getWifiPassword(),
                'localRecommendations' => $guidebook->getLocalRecommendations(),
                'emergencyContacts' => $guidebook->getEmergencyContacts(),
                'checkoutInstructions' => $guidebook->getCheckoutInstructions(),
                'parkingInfo' => $guidebook->getParkingInfo(),
                'transportInfo' => $guidebook->getTransportInfo(),
            ] : null,
            'guests' => array_map(fn ($g) => [
                'firstName' => $g->getFirstName(),
                'lastName' => $g->getLastName(),
                'nationality' => $g->getNationality(),
            ], $guests),
            'availableExtras' => array_map(fn ($e) => [
                'id' => (string) $e->getId(),
                'name' => $e->getName(),
                'description' => $e->getDescription(),
                'price' => $e->getPrice(),
                'priceType' => $e->getPriceType(),
            ], $extras),
            'selfCheckin' => [
                'guestsRegistered' => \count($guests) > 0,
                'canRegisterGuests' => true,
                'registerUrl' => '/api/guest-portal/'.$token.'/register-guests',
            ],
        ]);
    }
}
