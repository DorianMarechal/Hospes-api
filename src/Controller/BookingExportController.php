<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class BookingExportController extends AbstractController
{
    public function __construct(
        private BookingRepository $bookingRepository,
    ) {
    }

    #[Route('/api/me/bookings/export', methods: ['GET'])]
    public function __invoke(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $bookings = $this->bookingRepository->createQueryBuilder('b')
            ->andWhere('b.customer = :user')
            ->setParameter('user', $user)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $response = new StreamedResponse(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'Lodging', 'Check-in', 'Check-out', 'Nights', 'Guests', 'Total (cents)', 'Status', 'Created']);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->getReference(),
                    $booking->getLodging()?->getName() ?? '',
                    $booking->getCheckin()?->format('Y-m-d') ?? '',
                    $booking->getCheckout()?->format('Y-m-d') ?? '',
                    $booking->getNumberOfNights(),
                    $booking->getGuestsCount(),
                    $booking->getTotalPrice(),
                    $booking->getStatus()->value,
                    $booking->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="bookings-export.csv"');

        return $response;
    }
}
