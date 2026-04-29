<?php

namespace App\Tests\Integration\Repository;

use App\Repository\IcalFeedRepository;
use App\Tests\Factory\IcalFeedFactory;
use App\Tests\Factory\LodgingFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class IcalFeedRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private IcalFeedRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(IcalFeedRepository::class);
    }

    public function testFindByLodgingReturnsFeedsForLodging(): void
    {
        $lodging = LodgingFactory::createOne();

        IcalFeedFactory::createOne(['lodging' => $lodging]);
        IcalFeedFactory::createOne(['lodging' => $lodging]);

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(2, $results);
    }

    public function testFindByLodgingExcludesOtherLodgings(): void
    {
        $lodging = LodgingFactory::createOne();
        $other = LodgingFactory::createOne();

        IcalFeedFactory::createOne(['lodging' => $lodging]);
        IcalFeedFactory::createOne(['lodging' => $other]);

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(1, $results);
    }

    public function testFindByLodgingReturnsEmptyWhenNoFeeds(): void
    {
        $lodging = LodgingFactory::createOne();

        $results = $this->repository->findByLodging($lodging->_real());

        $this->assertCount(0, $results);
    }
}
