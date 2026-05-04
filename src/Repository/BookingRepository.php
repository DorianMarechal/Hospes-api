<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Lodging;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return Booking[]
     */
    public function findByLodging(Lodging $lodging): array
    {
        return $this->findBy(['lodging' => $lodging]);
    }

    /**
     * @return Booking[]
     */
    public function findActiveInPeriod(Lodging $lodging, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.lodging = :lodging')
            ->andWhere('b.status NOT IN (:excluded)')
            ->andWhere('b.checkin < :to')
            ->andWhere('b.checkout > :from')
            ->setParameter('lodging', $lodging)
            ->setParameter('excluded', [\App\Enum\BookingStatus::CANCELLED])
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<Lodging> $lodgings
     *
     * @return array<string, list<Booking>> keyed by lodging UUID
     */
    public function findActiveOverlappingForLodgings(array $lodgings, \DateTimeImmutable $checkin, \DateTimeImmutable $checkout): array
    {
        if ([] === $lodgings) {
            return [];
        }

        $bookings = $this->createQueryBuilder('b')
            ->andWhere('b.lodging IN (:lodgings)')
            ->andWhere('b.status NOT IN (:excluded)')
            ->andWhere('b.checkin < :checkout')
            ->andWhere('b.checkout > :checkin')
            ->setParameter('lodgings', $lodgings)
            ->setParameter('excluded', [\App\Enum\BookingStatus::CANCELLED])
            ->setParameter('checkin', $checkin)
            ->setParameter('checkout', $checkout)
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($bookings as $booking) {
            $key = $booking->getLodging()?->getId()?->toRfc4122() ?? '';
            $grouped[$key][] = $booking;
        }

        return $grouped;
    }

    /**
     * @return Booking[]
     */
    public function findByCustomer(User $customer): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Booking[]
     */
    public function findByCustomerWithLodging(User $customer): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.lodging', 'l')
            ->addSelect('l')
            ->andWhere('b.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByReference(string $reference): ?Booking
    {
        return $this->findOneBy(['reference' => $reference]);
    }

    /**
     * @param list<string> $lodgingIds
     *
     * @return array{revenue: int, bookingsCount: int, occupiedNights: int}
     */
    public function aggregateStats(array $lodgingIds, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                COALESCE(SUM(b.total_price), 0) AS revenue,
                COUNT(*) AS bookings_count,
                COALESCE(SUM(
                    GREATEST(0,
                        EXTRACT(DAY FROM (
                            LEAST(b.checkout, :to_date) - GREATEST(b.checkin, :from_date)
                        ))
                    )
                ), 0) AS occupied_nights
            FROM booking b
            WHERE b.lodging_id = ANY(:lodging_ids)
              AND b.status NOT IN ('cancelled', 'pending')
              AND b.checkin < :to_date
              AND b.checkout > :from_date
        SQL;

        $result = $conn->executeQuery($sql, [
            'lodging_ids' => '{'.implode(',', $lodgingIds).'}',
            'from_date' => $from->format('Y-m-d'),
            'to_date' => $to->format('Y-m-d'),
        ]);

        $row = $result->fetchAssociative();

        return [
            'revenue' => (int) ($row['revenue'] ?? 0),
            'bookingsCount' => (int) ($row['bookings_count'] ?? 0),
            'occupiedNights' => (int) ($row['occupied_nights'] ?? 0),
        ];
    }
}
