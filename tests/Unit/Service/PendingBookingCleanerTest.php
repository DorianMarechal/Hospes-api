<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Service\PendingBookingCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class PendingBookingCleanerTest extends TestCase
{
    private PendingBookingCleaner $cleaner;
    private EntityManagerInterface $em;
    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->messageBus->method('dispatch')->willReturnCallback(fn ($msg) => new Envelope($msg));
        $this->cleaner = new PendingBookingCleaner($this->em, $this->messageBus);
    }

    public function testCleanExpiredBuildsCorrectQuery(): void
    {
        // First call: SELECT query for notification dispatch
        $selectQuery = $this->createMock(Query::class);
        $selectQuery->method('getResult')->willReturn([]);

        $selectQb = $this->createMock(QueryBuilder::class);
        $selectQb->method('select')->willReturnSelf();
        $selectQb->method('from')->willReturnSelf();
        $selectQb->method('where')->willReturnSelf();
        $selectQb->method('andWhere')->willReturnSelf();
        $selectQb->method('setParameter')->willReturnSelf();
        $selectQb->method('getQuery')->willReturn($selectQuery);

        // Second call: UPDATE query for bulk status change
        $updateQuery = $this->createMock(Query::class);
        $updateQuery->method('execute')->willReturn(3);

        $updateQb = $this->createMock(QueryBuilder::class);
        $updateQb->method('update')->with(Booking::class, 'b')->willReturnSelf();
        $updateQb->method('set')->willReturnSelf();
        $updateQb->method('where')->willReturnSelf();
        $updateQb->method('andWhere')->willReturnSelf();
        $updateQb->method('setParameter')->willReturnSelf();
        $updateQb->method('getQuery')->willReturn($updateQuery);

        $this->em->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $updateQb);

        $result = $this->cleaner->cleanExpired();

        $this->assertSame(3, $result);
    }

    public function testCleanExpiredReturnsZeroWhenNoExpired(): void
    {
        $selectQuery = $this->createMock(Query::class);
        $selectQuery->method('getResult')->willReturn([]);

        $selectQb = $this->createMock(QueryBuilder::class);
        $selectQb->method('select')->willReturnSelf();
        $selectQb->method('from')->willReturnSelf();
        $selectQb->method('where')->willReturnSelf();
        $selectQb->method('andWhere')->willReturnSelf();
        $selectQb->method('setParameter')->willReturnSelf();
        $selectQb->method('getQuery')->willReturn($selectQuery);

        $updateQuery = $this->createMock(Query::class);
        $updateQuery->method('execute')->willReturn(0);

        $updateQb = $this->createMock(QueryBuilder::class);
        $updateQb->method('update')->willReturnSelf();
        $updateQb->method('set')->willReturnSelf();
        $updateQb->method('where')->willReturnSelf();
        $updateQb->method('andWhere')->willReturnSelf();
        $updateQb->method('setParameter')->willReturnSelf();
        $updateQb->method('getQuery')->willReturn($updateQuery);

        $this->em->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $updateQb);

        $this->assertSame(0, $this->cleaner->cleanExpired());
    }

    public function testCleanExpiredSetsCancelledStatusAndNow(): void
    {
        $selectQuery = $this->createMock(Query::class);
        $selectQuery->method('getResult')->willReturn([]);

        $selectQb = $this->createMock(QueryBuilder::class);
        $selectQb->method('select')->willReturnSelf();
        $selectQb->method('from')->willReturnSelf();
        $selectQb->method('where')->willReturnSelf();
        $selectQb->method('andWhere')->willReturnSelf();
        $selectQb->method('setParameter')->willReturnSelf();
        $selectQb->method('getQuery')->willReturn($selectQuery);

        $updateQuery = $this->createMock(Query::class);
        $updateQuery->method('execute')->willReturn(1);

        $setParams = [];
        $updateQb = $this->createMock(QueryBuilder::class);
        $updateQb->method('update')->willReturnSelf();
        $updateQb->method('set')->willReturnSelf();
        $updateQb->method('where')->willReturnSelf();
        $updateQb->method('andWhere')->willReturnSelf();
        $updateQb->method('setParameter')->willReturnCallback(function (string $key, mixed $value) use ($updateQb, &$setParams) {
            $setParams[$key] = $value;

            return $updateQb;
        });
        $updateQb->method('getQuery')->willReturn($updateQuery);

        $this->em->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $updateQb);

        $this->cleaner->cleanExpired();

        $this->assertSame(BookingStatus::CANCELLED, $setParams['cancelled']);
        $this->assertSame(BookingStatus::PENDING, $setParams['pending']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $setParams['now']);
    }
}
