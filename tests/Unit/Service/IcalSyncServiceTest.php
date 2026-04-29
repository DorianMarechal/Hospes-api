<?php

namespace App\Tests\Unit\Service;

use App\Entity\BlockedDate;
use App\Entity\Booking;
use App\Entity\IcalFeed;
use App\Entity\Lodging;
use App\Enum\BookingStatus;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Service\IcalSyncService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IcalSyncServiceTest extends TestCase
{
    private IcalSyncService $service;
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $em;
    private BlockedDateRepository $blockedDateRepo;
    private BookingRepository $bookingRepo;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->blockedDateRepo = $this->createMock(BlockedDateRepository::class);
        $this->bookingRepo = $this->createMock(BookingRepository::class);

        $this->service = new IcalSyncService(
            $this->httpClient,
            $this->em,
            $this->blockedDateRepo,
            $this->bookingRepo,
        );
    }

    public function test_sync_returns_zero_when_no_lodging(): void
    {
        $feed = new IcalFeed();
        // lodging is null

        $this->assertSame(0, $this->service->sync($feed));
    }

    public function test_sync_creates_blocked_dates_from_ics(): void
    {
        $lodging = new Lodging();
        $feed = new IcalFeed();
        $feed->setLodging($lodging);
        $feed->setUrl('https://example.com/cal.ics');

        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20260501\r\nDTEND;VALUE=DATE:20260505\r\nSUMMARY:Airbnb booking\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($ics);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://example.com/cal.ics')
            ->willReturn($response);

        $this->bookingRepo->method('findByLodging')->willReturn([]);
        $this->blockedDateRepo->method('findBy')->willReturn([]);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function ($entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $result = $this->service->sync($feed);

        $this->assertSame(1, $result);
        $this->assertCount(1, $persisted);
        $this->assertInstanceOf(BlockedDate::class, $persisted[0]);
        $this->assertSame('Airbnb booking', $persisted[0]->getReason());
        $this->assertSame('ical', $persisted[0]->getSource());
    }

    public function test_sync_removes_existing_ical_blocked_dates(): void
    {
        $lodging = new Lodging();
        $feed = new IcalFeed();
        $feed->setLodging($lodging);
        $feed->setUrl('https://example.com/cal.ics');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR");

        $this->httpClient->method('request')->willReturn($response);
        $this->bookingRepo->method('findByLodging')->willReturn([]);

        $existingBlocked = new BlockedDate();
        $this->blockedDateRepo->method('findBy')
            ->with(['lodging' => $lodging, 'source' => 'ical'])
            ->willReturn([$existingBlocked]);

        $removed = [];
        $this->em->method('remove')->willReturnCallback(function ($entity) use (&$removed) {
            $removed[] = $entity;
        });

        $this->service->sync($feed);

        $this->assertCount(1, $removed);
        $this->assertSame($existingBlocked, $removed[0]);
    }

    public function test_sync_skips_events_conflicting_with_bookings(): void
    {
        $lodging = new Lodging();
        $feed = new IcalFeed();
        $feed->setLodging($lodging);
        $feed->setUrl('https://example.com/cal.ics');

        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20260501\r\nDTEND;VALUE=DATE:20260505\r\nSUMMARY:External\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($ics);
        $this->httpClient->method('request')->willReturn($response);

        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-05-03'));
        $booking->setCheckout(new \DateTimeImmutable('2026-05-07'));
        $booking->setStatus(BookingStatus::CONFIRMED);

        $this->bookingRepo->method('findByLodging')->willReturn([$booking]);
        $this->blockedDateRepo->method('findBy')->willReturn([]);

        $this->em->expects($this->never())->method('persist');

        $result = $this->service->sync($feed);

        $this->assertSame(0, $result);
    }

    public function test_sync_ignores_cancelled_bookings_for_conflict_check(): void
    {
        $lodging = new Lodging();
        $feed = new IcalFeed();
        $feed->setLodging($lodging);
        $feed->setUrl('https://example.com/cal.ics');

        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20260501\r\nDTEND;VALUE=DATE:20260505\r\nSUMMARY:External\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($ics);
        $this->httpClient->method('request')->willReturn($response);

        $booking = new Booking();
        $booking->setCheckin(new \DateTimeImmutable('2026-05-03'));
        $booking->setCheckout(new \DateTimeImmutable('2026-05-07'));
        $booking->setStatus(BookingStatus::CANCELLED);

        $this->bookingRepo->method('findByLodging')->willReturn([$booking]);
        $this->blockedDateRepo->method('findBy')->willReturn([]);

        $this->em->expects($this->once())->method('persist');

        $result = $this->service->sync($feed);

        $this->assertSame(1, $result);
    }

    public function test_sync_parses_datetime_with_timezone(): void
    {
        $lodging = new Lodging();
        $feed = new IcalFeed();
        $feed->setLodging($lodging);
        $feed->setUrl('https://example.com/cal.ics');

        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20260501T120000Z\r\nDTEND:20260505T120000Z\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($ics);
        $this->httpClient->method('request')->willReturn($response);
        $this->bookingRepo->method('findByLodging')->willReturn([]);
        $this->blockedDateRepo->method('findBy')->willReturn([]);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function ($entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $result = $this->service->sync($feed);

        $this->assertSame(1, $result);
        $this->assertSame('iCal import', $persisted[0]->getReason());
    }

    public function test_sync_skips_events_without_start_or_end(): void
    {
        $lodging = new Lodging();
        $feed = new IcalFeed();
        $feed->setLodging($lodging);
        $feed->setUrl('https://example.com/cal.ics');

        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:No dates\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($ics);
        $this->httpClient->method('request')->willReturn($response);
        $this->bookingRepo->method('findByLodging')->willReturn([]);
        $this->blockedDateRepo->method('findBy')->willReturn([]);

        $this->em->expects($this->never())->method('persist');

        $result = $this->service->sync($feed);

        $this->assertSame(0, $result);
    }

    public function test_sync_handles_multiple_events(): void
    {
        $lodging = new Lodging();
        $feed = new IcalFeed();
        $feed->setLodging($lodging);
        $feed->setUrl('https://example.com/cal.ics');

        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20260501\r\nDTEND;VALUE=DATE:20260503\r\nSUMMARY:First\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20260510\r\nDTEND;VALUE=DATE:20260512\r\nSUMMARY:Second\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($ics);
        $this->httpClient->method('request')->willReturn($response);
        $this->bookingRepo->method('findByLodging')->willReturn([]);
        $this->blockedDateRepo->method('findBy')->willReturn([]);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function ($entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $result = $this->service->sync($feed);

        $this->assertSame(2, $result);
        $this->assertCount(2, $persisted);
    }
}
