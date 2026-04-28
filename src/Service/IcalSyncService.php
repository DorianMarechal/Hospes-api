<?php

namespace App\Service;

use App\Entity\BlockedDate;
use App\Entity\IcalFeed;
use App\Enum\BookingStatus;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IcalSyncService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $em,
        private BlockedDateRepository $blockedDateRepository,
        private BookingRepository $bookingRepository,
    ) {
    }

    public function sync(IcalFeed $feed): int
    {
        $lodging = $feed->getLodging();
        if (!$lodging) {
            return 0;
        }

        $response = $this->httpClient->request('GET', $feed->getUrl());
        $icsContent = $response->getContent();

        $events = $this->parseIcs($icsContent);
        $existingBookings = $this->bookingRepository->findByLodging($lodging);

        // Remove existing ical-sourced blocked dates for this lodging
        $existingBlocked = $this->blockedDateRepository->findBy([
            'lodging' => $lodging,
            'source' => 'ical',
        ]);
        foreach ($existingBlocked as $blocked) {
            $this->em->remove($blocked);
        }

        $created = 0;
        foreach ($events as $event) {
            if (!$event['start'] || !$event['end']) {
                continue;
            }

            // Skip if conflicts with an existing booking
            if ($this->conflictsWithBooking($event['start'], $event['end'], $existingBookings)) {
                continue;
            }

            $blocked = new BlockedDate();
            $blocked->setLodging($lodging);
            $blocked->setStartDate($event['start']);
            $blocked->setEndDate($event['end']);
            $blocked->setReason($event['summary'] ?? 'iCal import');
            $blocked->setSource('ical');
            $blocked->setCreatedAt(new \DateTimeImmutable());
            $blocked->setUpdatedAt(new \DateTimeImmutable());

            $this->em->persist($blocked);
            ++$created;
        }

        $this->em->flush();

        return $created;
    }

    /**
     * @return array<array{start: ?\DateTimeImmutable, end: ?\DateTimeImmutable, summary: ?string}>
     */
    private function parseIcs(string $content): array
    {
        $events = [];
        $inEvent = false;
        $current = ['start' => null, 'end' => null, 'summary' => null];

        $lines = preg_split('/\r\n|\r|\n/', $content);
        if (!$lines) {
            return [];
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ('BEGIN:VEVENT' === $line) {
                $inEvent = true;
                $current = ['start' => null, 'end' => null, 'summary' => null];
            } elseif ('END:VEVENT' === $line) {
                $inEvent = false;
                $events[] = $current;
            } elseif ($inEvent) {
                if (str_starts_with($line, 'DTSTART')) {
                    $current['start'] = $this->parseDateLine($line);
                } elseif (str_starts_with($line, 'DTEND')) {
                    $current['end'] = $this->parseDateLine($line);
                } elseif (str_starts_with($line, 'SUMMARY:')) {
                    $current['summary'] = substr($line, 8);
                }
            }
        }

        return $events;
    }

    private function parseDateLine(string $line): ?\DateTimeImmutable
    {
        // Handle formats like DTSTART;VALUE=DATE:20260501 or DTSTART:20260501T120000Z
        $parts = explode(':', $line, 2);
        $value = $parts[1] ?? '';

        if ('' === $value) {
            return null;
        }

        // DATE only (YYYYMMDD)
        if (8 === \strlen($value)) {
            return \DateTimeImmutable::createFromFormat('Ymd', $value) ?: null;
        }

        // DATETIME (YYYYMMDDTHHmmssZ or YYYYMMDDTHHmmss)
        $date = \DateTimeImmutable::createFromFormat('Ymd\THis\Z', $value);
        if ($date) {
            return $date;
        }

        return \DateTimeImmutable::createFromFormat('Ymd\THis', $value) ?: null;
    }

    /**
     * @param \App\Entity\Booking[] $bookings
     */
    private function conflictsWithBooking(\DateTimeImmutable $start, \DateTimeImmutable $end, array $bookings): bool
    {
        foreach ($bookings as $booking) {
            if (BookingStatus::CANCELLED === $booking->getStatus()) {
                continue;
            }

            if ($start < $booking->getCheckout() && $end > $booking->getCheckin()) {
                return true;
            }
        }

        return false;
    }
}
