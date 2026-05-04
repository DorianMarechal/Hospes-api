<?php

namespace App\Tests\Unit\Service;

use App\Channel\ChannelManagerFactory;
use App\Channel\ChannelManagerInterface;
use App\Entity\Booking;
use App\Entity\ChannelBooking;
use App\Entity\ChannelConnection;
use App\Entity\Lodging;
use App\Enum\Channel;
use App\Enum\ChannelBookingStatus;
use App\Repository\ChannelBookingRepository;
use App\Service\AvailabilityResolver;
use App\Service\BookingReferenceGenerator;
use App\Service\ChannelSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ChannelSyncServiceTest extends TestCase
{
    private ChannelSyncService $service;
    private ChannelManagerFactory $channelFactory;
    private ChannelManagerInterface $channelManager;
    private ChannelBookingRepository $channelBookingRepository;
    private EntityManagerInterface $em;
    private AvailabilityResolver $availabilityResolver;
    private BookingReferenceGenerator $referenceGenerator;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->channelFactory = $this->createMock(ChannelManagerFactory::class);
        $this->channelManager = $this->createMock(ChannelManagerInterface::class);
        $this->channelBookingRepository = $this->createMock(ChannelBookingRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->availabilityResolver = $this->createMock(AvailabilityResolver::class);
        $this->referenceGenerator = $this->createMock(BookingReferenceGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new ChannelSyncService(
            $this->channelFactory,
            $this->channelBookingRepository,
            $this->em,
            $this->availabilityResolver,
            $this->referenceGenerator,
            $this->logger,
        );
    }

    private function buildConnection(?Channel $channel, ?Lodging $lodging = null): ChannelConnection
    {
        $connection = new ChannelConnection();
        if (null !== $channel) {
            $connection->setChannel($channel);
        }
        if (null !== $lodging) {
            $connection->setLodging($lodging);
        }
        $connection->setCreatedAt(new \DateTimeImmutable());

        return $connection;
    }

    private function buildReservation(string $externalId = 'EXT-001', string $status = 'confirmed'): array
    {
        return [
            'external_id' => $externalId,
            'guest_name' => 'Alice Dupont',
            'checkin' => '2026-07-01',
            'checkout' => '2026-07-05',
            'guests' => 2,
            'status' => $status,
            'total_price' => 40000,
        ];
    }

    public function testSyncReturnsZeroWhenChannelIsNullOnConnection(): void
    {
        // ChannelConnection with no channel set — getChannel() returns null
        $connection = new ChannelConnection();
        $connection->setCreatedAt(new \DateTimeImmutable());

        $this->channelFactory->expects($this->never())->method('get');

        $result = $this->service->sync($connection);

        $this->assertSame(0, $result);
    }

    public function testSyncImportsBookingWhenAvailabilityCheckPasses(): void
    {
        $lodging = new Lodging();
        $connection = $this->buildConnection(Channel::AIRBNB, $lodging);
        $reservation = $this->buildReservation('EXT-001');

        $this->channelFactory->method('get')->willReturn($this->channelManager);
        $this->channelManager->method('syncBookings')->willReturn([$reservation]);
        $this->channelManager->method('syncAvailability');

        // No existing channel booking for this external ID
        $this->channelBookingRepository->method('findByExternalId')->willReturn(null);

        // Booking repository returns no existing bookings
        $bookingRepo = $this->createMock(EntityRepository::class);
        $bookingRepo->method('findBy')->willReturn([]);
        $this->em->method('getRepository')->with(Booking::class)->willReturn($bookingRepo);

        // Availability passes
        $this->availabilityResolver->method('isAvailable')->willReturn(true);

        $this->referenceGenerator->method('generate')->willReturn('HOS-AABBCCDD-26');

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function ($entity) use (&$persisted) {
            $persisted[] = $entity;
        });
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->sync($connection);

        $this->assertSame(1, $result);
        // Two persisted entities: Booking + ChannelBooking
        $this->assertCount(2, $persisted);
        $this->assertInstanceOf(Booking::class, $persisted[0]);
        $this->assertInstanceOf(ChannelBooking::class, $persisted[1]);
    }

    public function testSyncSkipsBookingThatConflictsWithLocalAvailability(): void
    {
        $lodging = new Lodging();
        $connection = $this->buildConnection(Channel::AIRBNB, $lodging);
        $reservation = $this->buildReservation('EXT-CONFLICT');

        $this->channelFactory->method('get')->willReturn($this->channelManager);
        $this->channelManager->method('syncBookings')->willReturn([$reservation]);
        $this->channelManager->method('syncAvailability');

        $this->channelBookingRepository->method('findByExternalId')->willReturn(null);

        $bookingRepo = $this->createMock(EntityRepository::class);
        $bookingRepo->method('findBy')->willReturn([]);
        $this->em->method('getRepository')->with(Booking::class)->willReturn($bookingRepo);

        // Availability check fails — conflict detected
        $this->availabilityResolver->method('isAvailable')->willReturn(false);

        $this->logger->expects($this->atLeastOnce())->method('warning');

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->sync($connection);

        $this->assertSame(0, $result);
    }

    public function testSyncUpdatesExistingChannelBookingStatusOnResync(): void
    {
        $lodging = new Lodging();
        $connection = $this->buildConnection(Channel::BOOKING_COM, $lodging);
        $reservation = $this->buildReservation('EXT-002', 'cancelled');

        $this->channelFactory->method('get')->willReturn($this->channelManager);
        $this->channelManager->method('syncBookings')->willReturn([$reservation]);
        $this->channelManager->method('syncAvailability');

        // Build an existing ChannelBooking in CONFIRMED state
        $booking = new Booking();
        $booking->setReference('HOS-EXISTING-26');
        $booking->setCheckin(new \DateTimeImmutable('2026-07-01'));
        $booking->setCheckout(new \DateTimeImmutable('2026-07-05'));
        $booking->setGuestsCount(2);
        $booking->setNumberOfNights(4);
        $booking->setNightsTotal(40000);
        $booking->setCleaningFee(0);
        $booking->setTouristTaxTotal(0);
        $booking->setDepositAmount(0);
        $booking->setTotalPrice(40000);
        $booking->setSource('channel_manager');
        $booking->setCreatedAt(new \DateTimeImmutable());
        $booking->setUpdatedAt(new \DateTimeImmutable());

        $existingChannelBooking = new ChannelBooking();
        $existingChannelBooking->setBooking($booking);
        $existingChannelBooking->setChannel(Channel::BOOKING_COM);
        $existingChannelBooking->setExternalReservationId('EXT-002');
        $existingChannelBooking->setExternalStatus(ChannelBookingStatus::CONFIRMED);
        $existingChannelBooking->setImportedAt(new \DateTimeImmutable());

        $this->channelBookingRepository->method('findByExternalId')->willReturn($existingChannelBooking);

        // No new persist should happen (update path, not import path)
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->sync($connection);

        // No new booking imported
        $this->assertSame(0, $result);
        // Status updated to CANCELLED on both ChannelBooking and Booking
        $this->assertSame(ChannelBookingStatus::CANCELLED, $existingChannelBooking->getExternalStatus());
        $this->assertNotNull($existingChannelBooking->getLastSyncAt());
    }
}
