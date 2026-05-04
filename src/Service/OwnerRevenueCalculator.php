<?php

namespace App\Service;

use App\Dto\OwnerLodgingRevenue;
use App\Dto\OwnerStatement;
use App\Entity\Lodging;
use App\Entity\PropertyOwner;
use App\Enum\BookingStatus;
use App\Enum\PaymentStatus;
use App\Repository\LodgingRepository;
use Doctrine\ORM\EntityManagerInterface;

class OwnerRevenueCalculator
{
    public function __construct(
        private EntityManagerInterface $em,
        private LodgingRepository $lodgingRepository,
    ) {
    }

    public function calculateForLodging(Lodging $lodging, PropertyOwner $owner): OwnerLodgingRevenue
    {
        $result = $this->em->createQuery(
            'SELECT COALESCE(SUM(p.amount), 0) as gross, COUNT(DISTINCT b.id) as cnt
             FROM App\Entity\Payment p
             JOIN p.booking b
             WHERE b.lodging = :lodging
             AND p.status = :status
             AND b.status IN (:statuses)'
        )
            ->setParameter('lodging', $lodging)
            ->setParameter('status', PaymentStatus::SUCCEEDED)
            ->setParameter('statuses', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
            ->getSingleResult();

        $gross = (int) $result['gross'];
        $bookingCount = (int) $result['cnt'];
        $rate = (float) $owner->getCommissionRate();
        $commission = (int) round($gross * $rate / 100);
        $net = $gross - $commission;

        return new OwnerLodgingRevenue(
            grossRevenue: $gross,
            commission: $commission,
            netRevenue: $net,
            currency: $lodging->getCurrency(),
            bookingCount: $bookingCount,
            commissionRate: $owner->getCommissionRate() ?? '0',
        );
    }

    /**
     * @return OwnerStatement[]
     */
    public function calculateStatements(PropertyOwner $owner): array
    {
        $lodgings = $this->lodgingRepository->findBy(['propertyOwner' => $owner]);
        if (empty($lodgings)) {
            return [];
        }

        $results = $this->em->createQuery(
            "SELECT l.id as lodging_id, l.name as lodging_name, l.currency,
                    SUBSTRING(p.createdAt, 1, 7) as month,
                    COALESCE(SUM(p.amount), 0) as gross,
                    COUNT(DISTINCT b.id) as cnt
             FROM App\Entity\Payment p
             JOIN p.booking b
             JOIN b.lodging l
             WHERE l.propertyOwner = :owner
             AND p.status = :status
             AND b.status IN (:statuses)
             GROUP BY l.id, l.name, l.currency, month
             ORDER BY month DESC, l.name ASC"
        )
            ->setParameter('owner', $owner)
            ->setParameter('status', PaymentStatus::SUCCEEDED)
            ->setParameter('statuses', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
            ->getResult();

        $rate = (float) $owner->getCommissionRate();
        $statements = [];

        foreach ($results as $row) {
            $gross = (int) $row['gross'];
            $commission = (int) round($gross * $rate / 100);

            $statements[] = new OwnerStatement(
                month: $row['month'],
                lodgingName: $row['lodging_name'],
                lodgingId: $row['lodging_id'],
                grossRevenue: $gross,
                commission: $commission,
                netRevenue: $gross - $commission,
                currency: $row['currency'],
                bookingCount: (int) $row['cnt'],
            );
        }

        return $statements;
    }
}
