<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Service\InvoiceGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class InvoiceController extends AbstractController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private InvoiceGenerator $invoiceGenerator,
    ) {
    }

    #[Route('/api/bookings/{id}/invoice', methods: ['GET'])]
    public function __invoke(string $id): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $booking = $this->bookingRepository->find(Uuid::fromString($id));
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        // Only customer, host, or admin can access invoices
        $isCustomer = $booking->getCustomer()?->getId()?->equals($user->getId());
        $isHost = $booking->getLodging()?->getHost()?->getUser()?->getId()?->equals($user->getId());
        $isAdmin = \in_array('ROLE_ADMIN', $user->getRoles(), true);

        if (!$isCustomer && !$isHost && !$isAdmin) {
            throw new AccessDeniedHttpException('You do not have access to this invoice');
        }

        if (!\in_array($booking->getStatus(), [BookingStatus::CONFIRMED, BookingStatus::COMPLETED], true)) {
            throw new HttpException(422, 'Invoice is only available for confirmed or completed bookings');
        }

        $pdf = $this->invoiceGenerator->generate($booking);
        $filename = sprintf('facture-%s.pdf', $booking->getReference());

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }
}
