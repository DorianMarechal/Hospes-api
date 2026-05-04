<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RevenueExportController extends AbstractController
{
    public function __construct(
        private PaymentRepository $paymentRepository,
    ) {
    }

    #[Route('/api/me/revenue/export', methods: ['GET'])]
    public function __invoke(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $hostProfile = $user->getHostProfile();
        if (null === $hostProfile) {
            throw new AccessDeniedHttpException('Host profile required');
        }

        $lodgingIds = $hostProfile->getLodgings()->map(fn ($l) => $l->getId())->toArray();
        $payments = $this->paymentRepository->findReceivedByHost($lodgingIds);

        $response = new StreamedResponse(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Booking Reference', 'Lodging', 'Amount (cents)', 'Type', 'Method', 'Status', 'Provider', 'Date']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->getBooking()?->getReference() ?? '',
                    $payment->getBooking()?->getLodging()?->getName() ?? '',
                    $payment->getAmount(),
                    $payment->getType()->value,
                    $payment->getMethod()->value,
                    $payment->getStatus()->value,
                    $payment->getProvider() ?? '',
                    $payment->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="revenue-export.csv"');

        return $response;
    }
}
