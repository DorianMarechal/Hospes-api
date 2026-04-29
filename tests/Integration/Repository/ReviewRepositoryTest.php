<?php

namespace App\Tests\Integration\Repository;

use App\Repository\ReviewRepository;
use App\Tests\Factory\BookingFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\ReviewFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReviewRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private ReviewRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(ReviewRepository::class);
    }

    public function test_find_by_lodging_returns_reviews_for_that_lodging(): void
    {
        $lodging = LodgingFactory::createOne();
        $otherLodging = LodgingFactory::createOne();

        ReviewFactory::createOne(['lodging' => $lodging]);
        ReviewFactory::createOne(['lodging' => $lodging]);
        ReviewFactory::createOne(['lodging' => $otherLodging]);

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(2, $results);
    }

    public function test_find_by_lodging_returns_ordered_by_created_at_desc(): void
    {
        $lodging = LodgingFactory::createOne();

        $review1 = ReviewFactory::createOne([
            'lodging' => $lodging,
            'createdAt' => new \DateTimeImmutable('2026-01-01'),
        ]);
        $review2 = ReviewFactory::createOne([
            'lodging' => $lodging,
            'createdAt' => new \DateTimeImmutable('2026-03-01'),
        ]);

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(2, $results);
        $this->assertSame($review2->getId()->toRfc4122(), $results[0]->getId()->toRfc4122());
    }

    public function test_find_by_customer_returns_reviews_for_that_customer(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $otherCustomer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        ReviewFactory::createOne(['customer' => $customer]);
        ReviewFactory::createOne(['customer' => $otherCustomer]);

        $results = $this->repository->findByCustomer($customer->_real());

        $this->assertCount(1, $results);
    }

    public function test_find_by_customer_returns_empty_when_no_reviews(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $results = $this->repository->findByCustomer($customer->_real());

        $this->assertCount(0, $results);
    }
}
