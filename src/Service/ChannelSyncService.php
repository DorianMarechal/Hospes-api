<?php

namespace App\Service;

use App\Channel\ChannelManagerFactory;
use App\Entity\Booking;
use App\Entity\ChannelBooking;
use App\Entity\ChannelConnection;
use App\Enum\BookingStatus;
use App\Enum\ChannelBookingStatus;
use App\Repository\ChannelBookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ChannelSyncService
{
    public function __construct(
        private ChannelManagerFactory $channelFactory,
        private ChannelBookingRepository $channelBookingRepository,
        private EntityManagerInterface $em,
        private AvailabilityResolver $availabilityResolver,
        private BookingReferenceGenerator $referenceGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public function sync(ChannelConnection $connection): int
    {
        $channel = $connection->getChannel();
        if (null === $channel) {
            return 0;
        }

        $manager = $this->channelFactory->get($channel);
        $count = 0;

        // Pull reservations from channel
        $reservations = $manager->syncBookings($connection);

        // Pre-load bookings and blocked dates once to avoid N+1
        $lodging = $connection->getLodging();
        $existingBookings = null !== $lodging
            ? $this->em->getRepository(Booking::class)->findBy(['lodging' => $lodging])
            : [];
        $blockedDates = null !== $lodging ? $lodging->getBlockedDates()->toArray() : [];

        foreach ($reservations as $reservation) {
            $externalId = $reservation['external_id'];
            $existing = $this->channelBookingRepository->findByExternalId($channel, $externalId);

            if (null !== $existing) {
                $this->updateExistingBooking($existing, $reservation);
            } else {
                if ($this->importBooking($connection, $reservation, $existingBookings, $blockedDates)) {
                    ++$count;
                }
            }
        }

        // Push local availability to channel
        $this->pushAvailability($connection, $manager);

        $connection->setLastSyncAt(new \DateTimeImmutable());
        $this->em->flush();

        return $count;
    }

    /**
     * @param array{external_id: string, guest_name: string, checkin: string, checkout: string, guests: int, status: string, total_price: int} $reservation
     * @param Booking[]                                                                                                                        $existingBookings
     * @param \App\Entity\BlockedDate[]                                                                                                        $blockedDates
     */
    private function importBooking(ChannelConnection $connection, array $reservation, array $existingBookings, array $blockedDates): bool
    {
        $lodging = $connection->getLodging();
        if (null === $lodging) {
            return false;
        }

        $checkin = \DateTimeImmutable::createFromFormat('Y-m-d', $reservation['checkin']);
        $checkout = \DateTimeImmutable::createFromFormat('Y-m-d', $reservation['checkout']);

        if (false === $checkin || false === $checkout) {
            $this->logger->warning('Channel booking has invalid dates', [
                'external_id' => $reservation['external_id'],
            ]);

            return false;
        }

        if (!$this->availabilityResolver->isAvailable($lodging, $checkin, $checkout, $existingBookings, $blockedDates, null)) {
            $this->logger->warning('Channel booking conflicts with local availability', [
                'external_id' => $reservation['external_id'],
                'lodging' => (string) $lodging->getId(),
            ]);

            return false;
        }

        $now = new \DateTimeImmutable();
        $numberOfNights = (int) $checkin->diff($checkout)->days;

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setReference($this->referenceGenerator->generate());
        $booking->setCheckin($checkin);
        $booking->setCheckout($checkout);
        $booking->setGuestsCount($reservation['guests']);
        $booking->setNumberOfNights($numberOfNights);
        $booking->setNightsTotal($reservation['total_price']);
        $booking->setCleaningFee(0);
        $booking->setTouristTaxTotal(0);
        $booking->setDepositAmount(0);
        $booking->setTotalPrice($reservation['total_price']);
        $cancellationPolicy = $lodging->getCancellationPolicy();
        if (null !== $cancellationPolicy) {
            $booking->setCancellationPolicy($cancellationPolicy);
        }
        $booking->setStatus($this->mapStatus($reservation['status']));
        $booking->setSource('channel_manager');
        $booking->setCreatedAt($now);
        $booking->setUpdatedAt($now);

        $this->em->persist($booking);

        $channelBooking = new ChannelBooking();
        $channelBooking->setBooking($booking);
        $channelBooking->setChannel($connection->getChannel());
        $channelBooking->setExternalReservationId($reservation['external_id']);
        $channelBooking->setExternalStatus($this->mapChannelStatus($reservation['status']));
        $channelBooking->setImportedAt($now);

        $this->em->persist($channelBooking);

        return true;
    }

    /**
     * @param array{external_id: string, guest_name: string, checkin: string, checkout: string, guests: int, status: string, total_price: int} $reservation
     */
    private function updateExistingBooking(ChannelBooking $channelBooking, array $reservation): void
    {
        $newStatus = $this->mapChannelStatus($reservation['status']);

        if ($channelBooking->getExternalStatus() !== $newStatus) {
            $channelBooking->setExternalStatus($newStatus);

            $booking = $channelBooking->getBooking();
            if (null !== $booking && ChannelBookingStatus::CANCELLED === $newStatus) {
                $booking->setStatus(BookingStatus::CANCELLED);
                $booking->setUpdatedAt(new \DateTimeImmutable());
            }
        }

        $channelBooking->setLastSyncAt(new \DateTimeImmutable());
    }

    private function pushAvailability(ChannelConnection $connection, \App\Channel\ChannelManagerInterface $manager): void
    {
        // Push availability — implementation depends on channel API
        // For now, this is a placeholder that channels will implement
        $manager->syncAvailability($connection, []);
    }

    private function mapStatus(string $externalStatus): BookingStatus
    {
        return match (strtolower($externalStatus)) {
            'confirmed', 'accepted' => BookingStatus::CONFIRMED,
            'cancelled', 'declined' => BookingStatus::CANCELLED,
            default => BookingStatus::CONFIRMED,
        };
    }

    private function mapChannelStatus(string $externalStatus): ChannelBookingStatus
    {
        return match (strtolower($externalStatus)) {
            'pending' => ChannelBookingStatus::PENDING,
            'confirmed', 'accepted' => ChannelBookingStatus::CONFIRMED,
            'cancelled', 'declined' => ChannelBookingStatus::CANCELLED,
            default => ChannelBookingStatus::CONFIRMED,
        };
    }
}
