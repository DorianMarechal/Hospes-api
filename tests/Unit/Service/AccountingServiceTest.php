<?php

namespace App\Tests\Unit\Service;

use App\Dto\AccountingTransaction;
use App\Entity\Booking;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\Payment;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Service\AccountingService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class AccountingServiceTest extends TestCase
{
    private AccountingService $service;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new AccountingService($this->em);
    }

    // -------------------------------------------------------------------------
    // getVatRate
    // -------------------------------------------------------------------------

    public function testGetVatRateReturnsTenForFrance(): void
    {
        $this->assertSame('10.00', $this->service->getVatRate('FR'));
    }

    public function testGetVatRateReturnsTenForFranceLowerCase(): void
    {
        $this->assertSame('10.00', $this->service->getVatRate('fr'));
    }

    public function testGetVatRateReturnsSevenForGermany(): void
    {
        $this->assertSame('7.00', $this->service->getVatRate('DE'));
    }

    public function testGetVatRateReturnsTenForSpain(): void
    {
        $this->assertSame('10.00', $this->service->getVatRate('ES'));
    }

    public function testGetVatRateReturnsTenForItaly(): void
    {
        $this->assertSame('10.00', $this->service->getVatRate('IT'));
    }

    public function testGetVatRateReturnsSixForPortugal(): void
    {
        $this->assertSame('6.00', $this->service->getVatRate('PT'));
    }

    public function testGetVatRateReturnsThreePointEightForSwitzerland(): void
    {
        $this->assertSame('3.80', $this->service->getVatRate('CH'));
    }

    public function testGetVatRateReturnsSixForBelgium(): void
    {
        $this->assertSame('6.00', $this->service->getVatRate('BE'));
    }

    public function testGetVatRateReturnsNineForNetherlands(): void
    {
        $this->assertSame('9.00', $this->service->getVatRate('NL'));
    }

    public function testGetVatRateReturnsTenForAustria(): void
    {
        $this->assertSame('10.00', $this->service->getVatRate('AT'));
    }

    public function testGetVatRateReturnsTwentyForUnitedKingdom(): void
    {
        $this->assertSame('20.00', $this->service->getVatRate('GB'));
    }

    public function testGetVatRateReturnsNullForUnknownCountry(): void
    {
        $this->assertNull($this->service->getVatRate('XX'));
    }

    public function testGetVatRateReturnsNullForEmptyString(): void
    {
        $this->assertNull($this->service->getVatRate(''));
    }

    // -------------------------------------------------------------------------
    // ACCOUNT_CODES via getTransactions
    // -------------------------------------------------------------------------

    private function buildQueryBuilderMock(array $payments): QueryBuilder
    {
        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
        $query->method('getResult')->willReturn($payments);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }

    private function createPaymentWithLodging(int $amount, PaymentType $type, string $country = 'FR'): Payment
    {
        $lodging = new Lodging();
        $countryRef = new \ReflectionProperty(Lodging::class, 'country');
        $countryRef->setValue($lodging, $country);

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setReference('HOS-TEST001-26');

        $payment = new Payment();
        $payment->setBooking($booking);
        $payment->setAmount($amount);
        $payment->setType($type);
        $payment->setMethod(PaymentMethod::CARD);
        $payment->setStatus(PaymentStatus::SUCCEEDED);

        $idRef = new \ReflectionProperty(Payment::class, 'id');
        $idRef->setValue($payment, Uuid::v7());

        return $payment;
    }

    public function testGetTransactionsPaymentUsesAccount706(): void
    {
        $hostProfile = new HostProfile();
        // PaymentType::BOOKING is treated as 'payment' (not a refund)
        $payment = $this->createPaymentWithLodging(15000, PaymentType::BOOKING);

        $qb = $this->buildQueryBuilderMock([$payment]);
        $this->em->method('createQueryBuilder')->willReturn($qb);

        $result = $this->service->getTransactions($hostProfile);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(AccountingTransaction::class, $result[0]);
        $this->assertSame('706', $result[0]->accountCode);
        $this->assertSame('payment', $result[0]->type);
    }

    public function testGetTransactionsRefundUsesAccount706(): void
    {
        $hostProfile = new HostProfile();
        $payment = $this->createPaymentWithLodging(5000, PaymentType::REFUND);

        $statusRef = new \ReflectionProperty(Payment::class, 'status');
        $statusRef->setValue($payment, PaymentStatus::REFUNDED);

        $qb = $this->buildQueryBuilderMock([$payment]);
        $this->em->method('createQueryBuilder')->willReturn($qb);

        $result = $this->service->getTransactions($hostProfile);

        $this->assertCount(1, $result);
        $this->assertSame('706', $result[0]->accountCode);
        $this->assertSame('refund', $result[0]->type);
    }

    public function testGetTransactionsRefundAmountIsNegative(): void
    {
        $hostProfile = new HostProfile();
        $payment = $this->createPaymentWithLodging(8000, PaymentType::REFUND);

        $qb = $this->buildQueryBuilderMock([$payment]);
        $this->em->method('createQueryBuilder')->willReturn($qb);

        $result = $this->service->getTransactions($hostProfile);

        $this->assertSame(-8000, $result[0]->amount);
    }

    public function testGetTransactionsPaymentAmountIsPositive(): void
    {
        $hostProfile = new HostProfile();
        $payment = $this->createPaymentWithLodging(12000, PaymentType::BOOKING);

        $qb = $this->buildQueryBuilderMock([$payment]);
        $this->em->method('createQueryBuilder')->willReturn($qb);

        $result = $this->service->getTransactions($hostProfile);

        $this->assertSame(12000, $result[0]->amount);
    }

    public function testGetTransactionsUsesVatRateForCountry(): void
    {
        $hostProfile = new HostProfile();
        $payment = $this->createPaymentWithLodging(10000, PaymentType::BOOKING, 'DE');

        $qb = $this->buildQueryBuilderMock([$payment]);
        $this->em->method('createQueryBuilder')->willReturn($qb);

        $result = $this->service->getTransactions($hostProfile);

        $this->assertSame('7.00', $result[0]->vatRate);
    }

    public function testGetTransactionsVatRateIsNullForUnknownCountry(): void
    {
        $hostProfile = new HostProfile();
        $payment = $this->createPaymentWithLodging(10000, PaymentType::BOOKING, 'JP');

        $qb = $this->buildQueryBuilderMock([$payment]);
        $this->em->method('createQueryBuilder')->willReturn($qb);

        $result = $this->service->getTransactions($hostProfile);

        $this->assertNull($result[0]->vatRate);
    }

    public function testGetTransactionsReturnsEmptyWhenNoPayments(): void
    {
        $hostProfile = new HostProfile();

        $qb = $this->buildQueryBuilderMock([]);
        $this->em->method('createQueryBuilder')->willReturn($qb);

        $result = $this->service->getTransactions($hostProfile);

        $this->assertSame([], $result);
    }

    public function testGetTransactionsAppliesFromDateFilter(): void
    {
        $hostProfile = new HostProfile();

        $capturedParams = [];
        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
        $query->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnCallback(function (string $key, mixed $value) use ($qb, &$capturedParams) {
            $capturedParams[$key] = $value;

            return $qb;
        });
        $qb->method('getQuery')->willReturn($query);

        $this->em->method('createQueryBuilder')->willReturn($qb);

        $this->service->getTransactions($hostProfile, '2026-01-01');

        $this->assertArrayHasKey('from', $capturedParams);
        $this->assertInstanceOf(\DateTimeImmutable::class, $capturedParams['from']);
    }

    public function testGetTransactionsAppliesDateRangeFilters(): void
    {
        $hostProfile = new HostProfile();

        $capturedParams = [];
        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
        $query->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnCallback(function (string $key, mixed $value) use ($qb, &$capturedParams) {
            $capturedParams[$key] = $value;

            return $qb;
        });
        $qb->method('getQuery')->willReturn($query);

        $this->em->method('createQueryBuilder')->willReturn($qb);

        $this->service->getTransactions($hostProfile, '2026-01-01', '2026-03-31');

        $this->assertArrayHasKey('from', $capturedParams);
        $this->assertArrayHasKey('to', $capturedParams);
        $this->assertInstanceOf(\DateTimeImmutable::class, $capturedParams['to']);
    }
}
