<?php

namespace App\Service;

use App\Entity\Booking;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvoiceGenerator
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function generate(Booking $booking): string
    {
        $lodging = $booking->getLodging();
        $hostProfile = $lodging?->getHost();
        $customer = $booking->getCustomer();

        $invoiceNumber = sprintf(
            'INV-%s-%s',
            $booking->getReference(),
            $booking->getCreatedAt()?->format('Ymd') ?? date('Ymd'),
        );

        $html = $this->twig->render('invoice/invoice.html.twig', [
            'booking' => $booking,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $booking->getCreatedAt()?->format('d/m/Y') ?? date('d/m/Y'),
            'host_profile' => $hostProfile,
            'host_legal_identifiers' => $hostProfile?->getHostLegalIdentifiers() ?? [],
            'customer' => $customer,
            'lodging_name' => $lodging?->getName() ?? '',
            'booking_nights' => $booking->getBookingNights(),
        ]);

        $options = new Options();
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
