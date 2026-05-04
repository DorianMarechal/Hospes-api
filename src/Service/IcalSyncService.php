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

        $url = $feed->getUrl();
        $this->validateUrl($url);

        $response = $this->httpClient->request('GET', $url, [
            'max_redirects' => 3,
            'timeout' => 10,
            'headers' => ['Accept' => 'text/calendar'],
        ]);
        $icsContent = $response->getContent();

        $events = $this->parseIcs($icsContent);
        $existingBookings = $this->bookingRepository->findByLodging($lodging);

        $existingBlocked = $this->blockedDateRepository->findBy([
            'lodging' => $lodging,
            'source' => 'ical',
        ]);

        // Build lookup of existing blocked dates by start+end for diff
        $existingMap = [];
        foreach ($existingBlocked as $blocked) {
            $key = $blocked->getStartDate()->format('Y-m-d').'|'.$blocked->getEndDate()->format('Y-m-d');
            $existingMap[$key] = $blocked;
        }

        $incomingKeys = [];
        $created = 0;

        foreach ($events as $event) {
            if (!$event['start'] || !$event['end']) {
                continue;
            }

            if ($this->conflictsWithBooking($event['start'], $event['end'], $existingBookings)) {
                continue;
            }

            $key = $event['start']->format('Y-m-d').'|'.$event['end']->format('Y-m-d');
            $incomingKeys[$key] = true;

            if (isset($existingMap[$key])) {
                // Already exists, update reason if changed
                $existing = $existingMap[$key];
                $newReason = $event['summary'] ?? 'iCal import';
                if ($existing->getReason() !== $newReason) {
                    $existing->setReason($newReason);
                    $existing->setUpdatedAt(new \DateTimeImmutable());
                }
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

        // Remove blocked dates no longer in the feed
        foreach ($existingMap as $key => $blocked) {
            if (!isset($incomingKeys[$key])) {
                $this->em->remove($blocked);
            }
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

    private function validateUrl(?string $url): void
    {
        if (null === $url || '' === $url) {
            throw new \InvalidArgumentException('iCal feed URL is empty');
        }

        $parsed = parse_url($url);
        if (false === $parsed || !isset($parsed['scheme'], $parsed['host'])) {
            throw new \InvalidArgumentException('Invalid iCal feed URL');
        }

        if (!\in_array(strtolower($parsed['scheme']), ['https'], true)) {
            throw new \InvalidArgumentException('iCal feed URL must use HTTPS');
        }

        $host = strtolower($parsed['host']);

        if ('localhost' === $host || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw new \InvalidArgumentException('iCal feed URL must not point to a local address');
        }

        $ip = gethostbyname($host);
        if ($ip !== $host) {
            if (false === filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE)) {
                throw new \InvalidArgumentException('iCal feed URL must not resolve to a private or reserved IP address');
            }
        }
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
