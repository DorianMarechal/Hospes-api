<?php

namespace App\Tests\Integration\Repository;

use App\Repository\BookingRepository;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BookingRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private BookingRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(BookingRepository::class);
    }

    public function testFindByLodgingReturnsBookingsForThatLodging(): void
    {
        $lodging = LodgingFactory::createOne();
        $otherLodging = LodgingFactory::createOne();

        BookingFactory::createOne(['lodging' => $lodging, 'checkin' => new \DateTimeImmutable('+10 days'), 'checkout' => new \DateTimeImmutable('+13 days')]);
        BookingFactory::createOne(['lodging' => $lodging, 'checkin' => new \DateTimeImmutable('+20 days'), 'checkout' => new \DateTimeImmutable('+23 days')]);
        BookingFactory::createOne(['lodging' => $otherLodging, 'checkin' => new \DateTimeImmutable('+30 days'), 'checkout' => new \DateTimeImmutable('+33 days')]);

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(2, $results);
    }

    public function testFindByLodgingReturnsEmptyWhenNoBookings(): void
    {
        $lodging = LodgingFactory::createOne();

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(0, $results);
    }

    public function testFindByCustomerReturnsBookingsOrderedByCreatedAtDesc(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $booking1 = BookingFactory::createOne([
            'customer' => $customer,
            'createdAt' => new \DateTimeImmutable('2026-01-01'),
            'checkin' => new \DateTimeImmutable('+40 days'),
            'checkout' => new \DateTimeImmutable('+43 days'),
        ]);
        $booking2 = BookingFactory::createOne([
            'customer' => $customer,
            'createdAt' => new \DateTimeImmutable('2026-03-01'),
            'checkin' => new \DateTimeImmutable('+50 days'),
            'checkout' => new \DateTimeImmutable('+53 days'),
        ]);

        $results = $this->repository->findByCustomer($customer->_real());

        $this->assertCount(2, $results);
        // Most recent first
        $this->assertSame($booking2->getId()->toRfc4122(), $results[0]->getId()->toRfc4122());
    }

    public function testFindByCustomerExcludesOtherCustomers(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $otherCustomer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        BookingFactory::createOne(['customer' => $customer, 'checkin' => new \DateTimeImmutable('+60 days'), 'checkout' => new \DateTimeImmutable('+63 days')]);
        BookingFactory::createOne(['customer' => $otherCustomer, 'checkin' => new \DateTimeImmutable('+70 days'), 'checkout' => new \DateTimeImmutable('+73 days')]);

        $results = $this->repository->findByCustomer($customer->_real());

        $this->assertCount(1, $results);
    }

    public function testFindByReferenceReturnsMatchingBooking(): void
    {
        $booking = BookingFactory::createOne(['reference' => 'HOS-TESTREF1-26']);

        $result = $this->repository->findByReference('HOS-TESTREF1-26');

        $this->assertNotNull($result);
        $this->assertSame($booking->getId()->toRfc4122(), $result->getId()->toRfc4122());
    }

    public function testFindByReferenceReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findByReference('NONEXISTENT');

        $this->assertNull($result);
    }
}
