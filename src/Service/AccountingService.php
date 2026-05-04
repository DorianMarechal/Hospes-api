<?php

namespace App\Service;

use App\Dto\AccountingTransaction;
use App\Entity\HostProfile;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use Doctrine\ORM\EntityManagerInterface;

class AccountingService
{
    private const array VAT_RATES = [
        'FR' => '10.00',
        'DE' => '7.00',
        'ES' => '10.00',
        'IT' => '10.00',
        'PT' => '6.00',
        'CH' => '3.80',
        'BE' => '6.00',
        'NL' => '9.00',
        'AT' => '10.00',
        'GB' => '20.00',
    ];

    private const array ACCOUNT_CODES = [
        'payment' => '706',
        'refund' => '706',
        'commission' => '622',
    ];

    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return AccountingTransaction[]
     */
    public function getTransactions(HostProfile $hostProfile, ?string $from = null, ?string $to = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p', 'b', 'l')
            ->from('App\Entity\Payment', 'p')
            ->join('p.booking', 'b')
            ->join('b.lodging', 'l')
            ->where('l.host = :host')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('host', $hostProfile)
            ->setParameter('statuses', [PaymentStatus::SUCCEEDED, PaymentStatus::REFUNDED])
            ->orderBy('p.createdAt', 'DESC');

        if (null !== $from) {
            $fromDate = \DateTimeImmutable::createFromFormat('Y-m-d', $from);
            if (false !== $fromDate) {
                $qb->andWhere('p.createdAt >= :from')
                    ->setParameter('from', $fromDate->setTime(0, 0));
            }
        }

        if (null !== $to) {
            $toDate = \DateTimeImmutable::createFromFormat('Y-m-d', $to);
            if (false !== $toDate) {
                $qb->andWhere('p.createdAt <= :to')
                    ->setParameter('to', $toDate->setTime(23, 59, 59));
            }
        }

        /** @var \App\Entity\Payment[] $payments */
        $payments = $qb->getQuery()->getResult();
        $transactions = [];

        foreach ($payments as $payment) {
            $booking = $payment->getBooking();
            $lodging = $booking?->getLodging();
            $country = $lodging?->getCountry() ?? 'FR';
            $isRefund = PaymentType::REFUND === $payment->getType();

            $transactions[] = new AccountingTransaction(
                id: (string) $payment->getId(),
                date: $payment->getCreatedAt()?->format('Y-m-d') ?? '',
                type: $isRefund ? 'refund' : 'payment',
                description: $isRefund
                    ? 'Remboursement '.$booking?->getReference()
                    : 'Paiement '.$booking?->getReference(),
                amount: $isRefund ? -($payment->getAmount() ?? 0) : ($payment->getAmount() ?? 0),
                currency: $payment->getCurrency(),
                reference: $booking?->getReference(),
                lodgingName: $lodging?->getName(),
                accountCode: self::ACCOUNT_CODES[$isRefund ? 'refund' : 'payment'],
                vatRate: self::VAT_RATES[$country] ?? null,
            );
        }

        return $transactions;
    }

    public function getVatRate(string $countryCode): ?string
    {
        return self::VAT_RATES[strtoupper($countryCode)] ?? null;
    }
}
