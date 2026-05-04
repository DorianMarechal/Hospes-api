<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AccountingService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class AccountingExportController
{
    public function __construct(
        private Security $security,
        private AccountingService $accountingService,
    ) {
    }

    #[Route('/api/me/accounting/export', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $hostProfile = $user->getHostProfile();
        if (null === $hostProfile) {
            throw new AccessDeniedHttpException('Host profile required.');
        }

        $from = $request->query->get('from');
        $to = $request->query->get('to');
        $transactions = $this->accountingService->getTransactions($hostProfile, $from, $to);

        $response = new StreamedResponse(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }

            fputcsv($handle, ['Date', 'Type', 'Description', 'Montant', 'Devise', 'Reference', 'Logement', 'Compte', 'TVA'], ';');

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->date,
                    $tx->type,
                    $tx->description,
                    number_format($tx->amount / 100, 2, ',', ''),
                    $tx->currency,
                    $tx->reference ?? '',
                    $tx->lodgingName ?? '',
                    $tx->accountCode,
                    $tx->vatRate ?? '',
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export-comptable.csv"');

        return $response;
    }
}
