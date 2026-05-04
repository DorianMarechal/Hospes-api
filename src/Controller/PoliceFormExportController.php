<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\GuestRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PoliceFormExportController
{
    public function __construct(
        private Security $security,
        private BookingRepository $bookingRepository,
        private GuestRepository $guestRepository,
    ) {
    }

    #[Route('/api/bookings/{bookingId}/police-form', methods: ['GET'])]
    public function __invoke(string $bookingId): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $booking = $this->bookingRepository->find($bookingId);
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        $isHost = $booking->getLodging()?->getHost()?->getUser()?->getId()?->equals($user->getId());
        if (!$isHost) {
            throw new AccessDeniedHttpException('Only the host can export the police form.');
        }

        $guests = $this->guestRepository->findByBooking($booking);
        $lodging = $booking->getLodging();

        $response = new StreamedResponse(function () use ($booking, $guests, $lodging) {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }

            // Header per French/Spanish/Italian police form requirements
            fputcsv($handle, [
                'Etablissement',
                'Adresse',
                'Reference Reservation',
                'Date Arrivee',
                'Date Depart',
                'Nom',
                'Prenom',
                'Nationalite',
                'Date Naissance',
                'Type Document',
                'Numero Document',
            ], ';');

            foreach ($guests as $guest) {
                fputcsv($handle, [
                    $lodging?->getName() ?? '',
                    $lodging?->getAddress().', '.$lodging?->getPostalCode().' '.$lodging?->getCity(),
                    $booking->getReference() ?? '',
                    $booking->getCheckin()?->format('d/m/Y') ?? '',
                    $booking->getCheckout()?->format('d/m/Y') ?? '',
                    $guest->getLastName() ?? '',
                    $guest->getFirstName() ?? '',
                    $guest->getNationality() ?? '',
                    $guest->getBirthDate()?->format('d/m/Y') ?? '',
                    null !== $guest->getIdType() ? $guest->getIdType()->value : '',
                    $guest->getIdNumber() ?? '',
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $ref = $booking->getReference() ?? 'unknown';
        $response->headers->set('Content-Disposition', \sprintf('attachment; filename="fiche-police-%s.csv"', preg_replace('/[^a-zA-Z0-9-]/', '', $ref)));

        return $response;
    }
}
