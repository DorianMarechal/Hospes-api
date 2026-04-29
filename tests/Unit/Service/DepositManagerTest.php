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

    public function test_create_from_booking_with_deposit(): void
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

    public function test_create_from_booking_with_zero_deposit_returns_null(): void
    {
        $booking = new Booking();
        $booking->setDepositAmount(0);

        $this->entityManager->expects($this->never())->method('persist');

        $deposit = $this->manager->createFromBooking($booking);

        $this->assertNull($deposit);
    }

    public function test_create_from_booking_with_null_deposit_returns_null(): void
    {
        $booking = new Booking();

        $this->entityManager->expects($this->never())->method('persist');

        $deposit = $this->manager->createFromBooking($booking);

        $this->assertNull($deposit);
    }

    public function test_release_deposit(): void
    {
        $deposit = new Deposit();
        $deposit->setAmount(30000);
        $deposit->setStatus(DepositStatus::HELD);

        $this->manager->release($deposit);

        $this->assertSame(DepositStatus::RELEASED, $deposit->getStatus());
        $this->assertNotNull($deposit->getReleasedAt());
        $this->assertNotNull($deposit->getUpdatedAt());
    }

    public function test_retain_full_amount(): void
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

    public function test_retain_partial_amount(): void
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

    public function test_retain_more_than_amount_is_fully_retained(): void
    {
        $deposit = new Deposit();
        $deposit->setAmount(30000);
        $deposit->setStatus(DepositStatus::HELD);

        $this->manager->retain($deposit, 50000, 'Extensive damage');

        $this->assertSame(DepositStatus::FULLY_RETAINED, $deposit->getStatus());
        $this->assertSame(50000, $deposit->getRetainedAmount());
    }
}
