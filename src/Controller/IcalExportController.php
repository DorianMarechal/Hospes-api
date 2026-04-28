<?php

namespace App\Controller;

use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class IcalExportController extends AbstractController
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
    ) {
    }

    #[Route('/api/lodgings/{id}/ical-export.ics', name: 'ical_export', methods: ['GET'])]
    public function __invoke(string $id): Response
    {
        $lodging = $this->lodgingRepository->find($id);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $bookings = $this->bookingRepository->findByLodging($lodging);
        $blockedDates = $this->blockedDateRepository->findByLodging($lodging);

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Hospes//API//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";

        foreach ($bookings as $booking) {
            if (\App\Enum\BookingStatus::CANCELLED === $booking->getStatus()) {
                continue;
            }

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= sprintf("UID:%s@hospes\r\n", $booking->getId());
            $ics .= sprintf("DTSTART;VALUE=DATE:%s\r\n", $booking->getCheckin()->format('Ymd'));
            $ics .= sprintf("DTEND;VALUE=DATE:%s\r\n", $booking->getCheckout()->format('Ymd'));
            $ics .= sprintf("SUMMARY:Booking %s\r\n", $booking->getReference());
            $ics .= "STATUS:CONFIRMED\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        foreach ($blockedDates as $blocked) {
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= sprintf("UID:blocked-%s@hospes\r\n", $blocked->getId());
            $ics .= sprintf("DTSTART;VALUE=DATE:%s\r\n", $blocked->getStartDate()->format('Ymd'));
            $ics .= sprintf("DTEND;VALUE=DATE:%s\r\n", $blocked->getEndDate()->format('Ymd'));
            $ics .= sprintf("SUMMARY:%s\r\n", $blocked->getReason() ?? 'Blocked');
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        return new Response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s.ics"', $lodging->getName()),
        ]);
    }
}
