<?php

namespace App\Tests\Integration\Repository;

use App\Repository\FavoriteRepository;
use App\Tests\Factory\FavoriteFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class FavoriteRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private FavoriteRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(FavoriteRepository::class);
    }

    public function testFindByUserReturnsUserFavorites(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $lodging1 = LodgingFactory::createOne();
        $lodging2 = LodgingFactory::createOne();

        FavoriteFactory::createOne(['user' => $user, 'lodging' => $lodging1]);
        FavoriteFactory::createOne(['user' => $user, 'lodging' => $lodging2]);

        $results = $this->repository->findByUser($user->_real());

        $this->assertCount(2, $results);
    }

    public function testFindByUserExcludesOtherUsers(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $other = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        FavoriteFactory::createOne(['user' => $user]);
        FavoriteFactory::createOne(['user' => $other]);

        $results = $this->repository->findByUser($user->_real());

        $this->assertCount(1, $results);
    }

    public function testFindByUserReturnsEmptyWhenNoFavorites(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $results = $this->repository->findByUser($user->_real());

        $this->assertCount(0, $results);
    }

    public function testFindByUserOrderedByCreatedAtDesc(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $older = FavoriteFactory::createOne(['user' => $user, 'createdAt' => new \DateTimeImmutable('2026-01-01')]);
        $newer = FavoriteFactory::createOne(['user' => $user, 'createdAt' => new \DateTimeImmutable('2026-03-01')]);

        $results = $this->repository->findByUser($user->_real());

        $this->assertCount(2, $results);
        $this->assertSame($newer->getId()->toRfc4122(), $results[0]->getId()->toRfc4122());
    }
}
