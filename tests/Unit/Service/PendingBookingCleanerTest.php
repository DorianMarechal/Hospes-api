<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Service\PendingBookingCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class PendingBookingCleanerTest extends TestCase
{
    private PendingBookingCleaner $cleaner;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->cleaner = new PendingBookingCleaner($this->em);
    }

    public function test_clean_expired_builds_correct_query(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn(3);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('update')->with(Booking::class, 'b')->willReturnSelf();
        $qb->method('set')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $result = $this->cleaner->cleanExpired();

        $this->assertSame(3, $result);
    }

    public function test_clean_expired_returns_zero_when_no_expired(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn(0);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('update')->willReturnSelf();
        $qb->method('set')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->em->method('createQueryBuilder')->willReturn($qb);

        $this->assertSame(0, $this->cleaner->cleanExpired());
    }

    public function test_clean_expired_sets_cancelled_status_and_now(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn(1);

        $setParams = [];
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('update')->willReturnSelf();
        $qb->method('set')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnCallback(function (string $key, mixed $value) use ($qb, &$setParams) {
            $setParams[$key] = $value;

            return $qb;
        });
        $qb->method('getQuery')->willReturn($query);

        $this->em->method('createQueryBuilder')->willReturn($qb);

        $this->cleaner->cleanExpired();

        $this->assertSame(BookingStatus::CANCELLED, $setParams['cancelled']);
        $this->assertSame(BookingStatus::PENDING, $setParams['pending']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $setParams['now']);
    }
}
