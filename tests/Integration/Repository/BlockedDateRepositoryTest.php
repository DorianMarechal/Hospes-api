<?php

namespace App\Tests\Integration\Repository;

use App\Repository\BlockedDateRepository;
use App\Tests\Factory\BlockedDateFactory;
use App\Tests\Factory\LodgingFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BlockedDateRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private BlockedDateRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(BlockedDateRepository::class);
    }

    public function testFindByLodgingReturnsBlockedDatesForLodging(): void
    {
        $lodging = LodgingFactory::createOne();

        BlockedDateFactory::createOne(['lodging' => $lodging, 'startDate' => new \DateTimeImmutable('+1 month'), 'endDate' => new \DateTimeImmutable('+1 month +2 days')]);
        BlockedDateFactory::createOne(['lodging' => $lodging, 'startDate' => new \DateTimeImmutable('+2 months'), 'endDate' => new \DateTimeImmutable('+2 months +2 days')]);

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(2, $results);
    }

    public function testFindByLodgingExcludesOtherLodgings(): void
    {
        $lodging = LodgingFactory::createOne();
        $other = LodgingFactory::createOne();

        BlockedDateFactory::createOne(['lodging' => $lodging]);
        BlockedDateFactory::createOne(['lodging' => $other]);

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(1, $results);
    }

    public function testFindByLodgingReturnsEmptyWhenNoBlockedDates(): void
    {
        $lodging = LodgingFactory::createOne();

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(0, $results);
    }
}
