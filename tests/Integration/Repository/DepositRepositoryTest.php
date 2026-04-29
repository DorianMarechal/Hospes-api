<?php

namespace App\Tests\Integration\Repository;

use App\Repository\DepositRepository;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\DepositFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DepositRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private DepositRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(DepositRepository::class);
    }

    public function test_find_by_booking_returns_deposit(): void
    {
        $booking = BookingFactory::createOne();
        $deposit = DepositFactory::createOne(['booking' => $booking]);

        $result = $this->repository->findByBooking($booking->_real());

        $this->assertNotNull($result);
        $this->assertSame($deposit->getId()->toRfc4122(), $result->getId()->toRfc4122());
    }

    public function test_find_by_booking_returns_null_when_no_deposit(): void
    {
        $booking = BookingFactory::createOne();

        $result = $this->repository->findByBooking($booking->_real());

        $this->assertNull($result);
    }

    public function test_find_by_booking_returns_correct_deposit(): void
    {
        $booking1 = BookingFactory::createOne();
        $booking2 = BookingFactory::createOne(['checkin' => new \DateTimeImmutable('+30 days'), 'checkout' => new \DateTimeImmutable('+33 days')]);

        $deposit1 = DepositFactory::createOne(['booking' => $booking1]);
        $deposit2 = DepositFactory::createOne(['booking' => $booking2]);

        $result = $this->repository->findByBooking($booking1->_real());

        $this->assertSame($deposit1->getId()->toRfc4122(), $result->getId()->toRfc4122());
    }
}
