<?php

namespace App\Controller;

use App\Entity\BookingExtra;
use App\Repository\BookingRepository;
use App\Repository\ExtraRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class BookingExtraController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private ExtraRepository $extraRepository,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/guest-portal/{token}/extras', methods: ['POST'])]
    public function addExtra(string $token, Request $request): JsonResponse
    {
        $booking = $this->bookingRepository->findOneBy(['guestPortalToken' => $token]);
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        $payload = json_decode($request->getContent(), true);
        $extraId = $payload['extraId'] ?? null;
        $quantity = max(1, (int) ($payload['quantity'] ?? 1));

        if (null === $extraId) {
            throw new HttpException(422, 'extraId is required.');
        }

        $extra = $this->extraRepository->find($extraId);
        if (null === $extra || !$extra->isEnabled()) {
            throw new NotFoundHttpException('Extra not found.');
        }

        $lodging = $booking->getLodging();
        if (null === $lodging || !$extra->getLodging()?->getId()?->equals($lodging->getId())) {
            throw new HttpException(422, 'This extra is not available for this lodging.');
        }

        $nights = $booking->getNumberOfNights() ?? 1;
        $guests = $booking->getGuestsCount() ?? 1;
        $unitPrice = $extra->calculateTotal($nights, $guests);
        $totalPrice = $unitPrice * $quantity;

        $bookingExtra = new BookingExtra();
        $bookingExtra->setBooking($booking);
        $bookingExtra->setExtra($extra);
        $bookingExtra->setQuantity($quantity);
        $bookingExtra->setUnitPrice($unitPrice);
        $bookingExtra->setTotalPrice($totalPrice);

        $this->em->persist($bookingExtra);

        // Recalculate booking total
        $currentTotal = $booking->getTotalPrice() ?? 0;
        $booking->setTotalPrice($currentTotal + $totalPrice);
        $booking->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return new JsonResponse([
            'extraName' => $extra->getName(),
            'quantity' => $quantity,
            'totalPrice' => $totalPrice,
            'bookingNewTotal' => $booking->getTotalPrice(),
        ], 201);
    }
}
