<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\Deposit;
use App\Enum\DepositStatus;
use App\Service\DepositManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DepositManagerTest extends TestCase
{
    private DepositManager $manager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->manager = new DepositManager($this->entityManager);
    }

    public function testCreateFromBookingWithDeposit(): void
    {
        $booking = new Booking();
        $booking->setDepositAmount(30000);

        $this->entityManager->expects($this->once())->method('persist');

        $deposit = $this->manager->createFromBooking($booking);

        $this->assertNotNull($deposit);
        $this->assertSame(30000, $deposit->getAmount());
        $this->assertSame(DepositStatus::HELD, $deposit->getStatus());
        $this->assertSame($booking, $deposit->getBooking());
        $this->assertNotNull($deposit->getCreatedAt());
    }

    public function testCreateFromBookingWithZeroDepositReturnsNull(): void
    {
        $booking = new Booking();
        $booking->setDepositAmount(0);

        $this->entityManager->expects($this->never())->method('persist');

        $deposit = $this->manager->createFromBooking($booking);

        $this->assertNull($deposit);
    }

    public function testCreateFromBookingWithNullDepositReturnsNull(): void
    {
        $booking = new Booking();

        $this->entityManager->expects($this->never())->method('persist');

        $deposit = $this->manager->createFromBooking($booking);

        $this->assertNull($deposit);
    }

    public function testReleaseDeposit(): void
    {
        $deposit = new Deposit();
        $deposit->setAmount(30000);
        $deposit->setStatus(DepositStatus::HELD);

        $this->manager->release($deposit);

        $this->assertSame(DepositStatus::RELEASED, $deposit->getStatus());
        $this->assertNotNull($deposit->getReleasedAt());
        $this->assertNotNull($deposit->getUpdatedAt());
    }

    public function testRetainFullAmount(): void
    {
        $deposit = new Deposit();
        $deposit->setAmount(30000);
        $deposit->setStatus(DepositStatus::HELD);

        $this->manager->retain($deposit, 30000, 'Damage to furniture');

        $this->assertSame(DepositStatus::FULLY_RETAINED, $deposit->getStatus());
        $this->assertSame(30000, $deposit->getRetainedAmount());
        $this->assertSame('Damage to furniture', $deposit->getRetainedReason());
        $this->assertNotNull($deposit->getUpdatedAt());
        $this->assertNull($deposit->getReleasedAt());
    }

    public function testRetainPartialAmount(): void
    {
        $deposit = new Deposit();
        $deposit->setAmount(30000);
        $deposit->setStatus(DepositStatus::HELD);

        $this->manager->retain($deposit, 10000, 'Broken glass');

        $this->assertSame(DepositStatus::PARTIALLY_RETAINED, $deposit->getStatus());
        $this->assertSame(10000, $deposit->getRetainedAmount());
        $this->assertSame('Broken glass', $deposit->getRetainedReason());
        $this->assertNotNull($deposit->getReleasedAt());
    }

    public function testRetainMoreThanAmountIsFullyRetained(): void
    {
        $deposit = new Deposit();
        $deposit->setAmount(30000);
        $deposit->setStatus(DepositStatus::HELD);

        $this->manager->retain($deposit, 50000, 'Extensive damage');

        $this->assertSame(DepositStatus::FULLY_RETAINED, $deposit->getStatus());
        $this->assertSame(50000, $deposit->getRetainedAmount());
    }
}
