<?php

namespace App\Tests\Unit\Service;

use App\Dto\OwnerLodgingRevenue;
use App\Dto\OwnerStatement;
use App\Entity\Lodging;
use App\Entity\PropertyOwner;
use App\Enum\BookingStatus;
use App\Enum\PaymentStatus;
use App\Repository\LodgingRepository;
use App\Service\OwnerRevenueCalculator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class OwnerRevenueCalculatorTest extends TestCase
{
    private OwnerRevenueCalculator $calculator;
    private EntityManagerInterface $em;
    private LodgingRepository $lodgingRepository;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->lodgingRepository = $this->createMock(LodgingRepository::class);
        $this->calculator = new OwnerRevenueCalculator($this->em, $this->lodgingRepository);
    }

    private function createLodging(string $currency = 'EUR'): Lodging
    {
        $lodging = new Lodging();
        $ref = new \ReflectionProperty(Lodging::class, 'currency');
        $ref->setValue($lodging, $currency);

        return $lodging;
    }

    private function createOwner(string $commissionRate): PropertyOwner
    {
        $owner = new PropertyOwner();
        $owner->setCommissionRate($commissionRate);

        return $owner;
    }

    private function mockSingleResultQuery(array $result): Query
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('setParameter')->willReturnSelf();
        $query->method('getSingleResult')->willReturn($result);

        return $query;
    }

    public function testCalculateForLodgingReturnsCorrectCommission(): void
    {
        $lodging = $this->createLodging();
        $owner = $this->createOwner('15');

        $query = $this->mockSingleResultQuery(['gross' => '10000', 'cnt' => '3']);

        $this->em->expects($this->once())
            ->method('createQuery')
            ->willReturn($query);

        $result = $this->calculator->calculateForLodging($lodging, $owner);

        $this->assertInstanceOf(OwnerLodgingRevenue::class, $result);
        $this->assertSame(10000, $result->grossRevenue);
        $this->assertSame(1500, $result->commission);
        $this->assertSame(8500, $result->netRevenue);
        $this->assertSame('EUR', $result->currency);
        $this->assertSame(3, $result->bookingCount);
        $this->assertSame('15', $result->commissionRate);
    }

    public function testCalculateForLodgingReturnsZeroWhenNoPayments(): void
    {
        $lodging = $this->createLodging();
        $owner = $this->createOwner('20');

        $query = $this->mockSingleResultQuery(['gross' => '0', 'cnt' => '0']);

        $this->em->method('createQuery')->willReturn($query);

        $result = $this->calculator->calculateForLodging($lodging, $owner);

        $this->assertSame(0, $result->grossRevenue);
        $this->assertSame(0, $result->commission);
        $this->assertSame(0, $result->netRevenue);
        $this->assertSame(0, $result->bookingCount);
    }

    public function testCalculateForLodgingCommissionRoundsCorrectly(): void
    {
        // 10001 gross at 15% = 1500.15 → rounds to 1500
        $lodging = $this->createLodging();
        $owner = $this->createOwner('15');

        $query = $this->mockSingleResultQuery(['gross' => '10001', 'cnt' => '1']);

        $this->em->method('createQuery')->willReturn($query);

        $result = $this->calculator->calculateForLodging($lodging, $owner);

        $this->assertSame(10001, $result->grossRevenue);
        $this->assertSame(1500, $result->commission);
        $this->assertSame(8501, $result->netRevenue);
    }

    public function testCalculateForLodgingWithZeroCommissionRate(): void
    {
        $lodging = $this->createLodging();
        $owner = $this->createOwner('0');

        $query = $this->mockSingleResultQuery(['gross' => '50000', 'cnt' => '5']);

        $this->em->method('createQuery')->willReturn($query);

        $result = $this->calculator->calculateForLodging($lodging, $owner);

        $this->assertSame(50000, $result->grossRevenue);
        $this->assertSame(0, $result->commission);
        $this->assertSame(50000, $result->netRevenue);
    }

    public function testCalculateForLodgingPreservesCurrency(): void
    {
        $lodging = $this->createLodging('USD');
        $owner = $this->createOwner('10');

        $query = $this->mockSingleResultQuery(['gross' => '20000', 'cnt' => '2']);

        $this->em->method('createQuery')->willReturn($query);

        $result = $this->calculator->calculateForLodging($lodging, $owner);

        $this->assertSame('USD', $result->currency);
    }

    public function testCalculateForLodgingUsesCorrectDqlParameters(): void
    {
        $lodging = $this->createLodging();
        $owner = $this->createOwner('10');

        $capturedParams = [];
        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
        $query->method('setParameter')->willReturnCallback(
            function (string $key, mixed $value) use ($query, &$capturedParams) {
                $capturedParams[$key] = $value;

                return $query;
            }
        );
        $query->method('getSingleResult')->willReturn(['gross' => '0', 'cnt' => '0']);

        $this->em->method('createQuery')->willReturn($query);

        $this->calculator->calculateForLodging($lodging, $owner);

        $this->assertArrayHasKey('lodging', $capturedParams);
        $this->assertSame($lodging, $capturedParams['lodging']);
        $this->assertArrayHasKey('status', $capturedParams);
        $this->assertSame(PaymentStatus::SUCCEEDED, $capturedParams['status']);
        $this->assertArrayHasKey('statuses', $capturedParams);
        $this->assertContains(BookingStatus::CONFIRMED, $capturedParams['statuses']);
        $this->assertContains(BookingStatus::COMPLETED, $capturedParams['statuses']);
    }

    public function testCalculateStatementsReturnsEmptyArrayWhenNoLodgings(): void
    {
        $owner = $this->createOwner('15');

        $this->lodgingRepository->method('findBy')->with(['propertyOwner' => $owner])->willReturn([]);

        $this->em->expects($this->never())->method('createQuery');

        $result = $this->calculator->calculateStatements($owner);

        $this->assertSame([], $result);
    }

    public function testCalculateStatementsReturnsOwnerStatements(): void
    {
        $owner = $this->createOwner('10');
        $lodgingId = Uuid::v7();

        $lodging = $this->createLodging();
        $ref = new \ReflectionProperty(Lodging::class, 'id');
        $ref->setValue($lodging, $lodgingId);

        $this->lodgingRepository->method('findBy')->willReturn([$lodging]);

        $queryResult = [
            [
                'lodging_id' => $lodgingId->toRfc4122(),
                'lodging_name' => 'Chalet Alpin',
                'currency' => 'EUR',
                'month' => '2026-04',
                'gross' => '30000',
                'cnt' => '2',
            ],
        ];

        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
        $query->method('setParameter')->willReturnSelf();
        $query->method('getResult')->willReturn($queryResult);

        $this->em->method('createQuery')->willReturn($query);

        $result = $this->calculator->calculateStatements($owner);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OwnerStatement::class, $result[0]);
        $this->assertSame('2026-04', $result[0]->month);
        $this->assertSame('Chalet Alpin', $result[0]->lodgingName);
        $this->assertSame(30000, $result[0]->grossRevenue);
        $this->assertSame(3000, $result[0]->commission);   // 30000 * 10% = 3000
        $this->assertSame(27000, $result[0]->netRevenue);
        $this->assertSame('EUR', $result[0]->currency);
        $this->assertSame(2, $result[0]->bookingCount);
    }

    public function testCalculateStatementsMultipleMonths(): void
    {
        $owner = $this->createOwner('20');
        $lodging = $this->createLodging();

        $this->lodgingRepository->method('findBy')->willReturn([$lodging]);

        $queryResult = [
            [
                'lodging_id' => 'some-uuid',
                'lodging_name' => 'Villa Mer',
                'currency' => 'EUR',
                'month' => '2026-05',
                'gross' => '10000',
                'cnt' => '1',
            ],
            [
                'lodging_id' => 'some-uuid',
                'lodging_name' => 'Villa Mer',
                'currency' => 'EUR',
                'month' => '2026-04',
                'gross' => '20000',
                'cnt' => '2',
            ],
        ];

        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
        $query->method('setParameter')->willReturnSelf();
        $query->method('getResult')->willReturn($queryResult);

        $this->em->method('createQuery')->willReturn($query);

        $result = $this->calculator->calculateStatements($owner);

        $this->assertCount(2, $result);
        $this->assertSame('2026-05', $result[0]->month);
        $this->assertSame(2000, $result[0]->commission);    // 10000 * 20%
        $this->assertSame('2026-04', $result[1]->month);
        $this->assertSame(4000, $result[1]->commission);    // 20000 * 20%
    }
}
