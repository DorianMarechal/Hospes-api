<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PropertyOwnerRepository;
use App\Service\OwnerRevenueCalculator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class OwnerStatementExportController
{
    public function __construct(
        private Security $security,
        private PropertyOwnerRepository $ownerRepository,
        private OwnerRevenueCalculator $revenueCalculator,
    ) {
    }

    #[Route('/api/owner/statements/export', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $owner = $this->ownerRepository->findByUser($user);
        if (null === $owner) {
            throw new AccessDeniedHttpException('You are not a property owner.');
        }

        $statements = $this->revenueCalculator->calculateStatements($owner);

        $response = new StreamedResponse(function () use ($statements) {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }

            fputcsv($handle, ['Mois', 'Logement', 'CA Brut', 'Commission', 'Net', 'Devise', 'Reservations'], ';');

            foreach ($statements as $statement) {
                fputcsv($handle, [
                    $statement->month,
                    $statement->lodgingName,
                    number_format($statement->grossRevenue / 100, 2, ',', ''),
                    number_format($statement->commission / 100, 2, ',', ''),
                    number_format($statement->netRevenue / 100, 2, ',', ''),
                    $statement->currency,
                    $statement->bookingCount,
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="releve-proprietaire.csv"');

        return $response;
    }
}
