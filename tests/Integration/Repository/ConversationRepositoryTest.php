<?php

namespace App\Tests\Integration\Repository;

use App\Repository\ConversationRepository;
use App\Tests\Factory\ConversationFactory;
use App\Tests\Factory\HostProfileFactory;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ConversationRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private ConversationRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(ConversationRepository::class);
    }

    public function testFindByCustomerReturnsCustomerConversations(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $hostProfile = HostProfileFactory::createOne();
        $lodging1 = LodgingFactory::createOne(['host' => $hostProfile]);
        $lodging2 = LodgingFactory::createOne(['host' => $hostProfile]);

        ConversationFactory::createOne(['customer' => $customer, 'lodging' => $lodging1, 'host' => $hostProfile->getUser()]);
        ConversationFactory::createOne(['customer' => $customer, 'lodging' => $lodging2, 'host' => $hostProfile->getUser()]);

        $results = $this->repository->findByCustomer($customer->_real());

        $this->assertCount(2, $results);
    }

    public function testFindByCustomerExcludesOtherCustomers(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);
        $other = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        ConversationFactory::createOne(['customer' => $customer]);
        ConversationFactory::createOne(['customer' => $other]);

        $results = $this->repository->findByCustomer($customer->_real());

        $this->assertCount(1, $results);
    }

    public function testFindByCustomerOrderedByUpdatedAtDesc(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $older = ConversationFactory::createOne(['customer' => $customer, 'updatedAt' => new \DateTimeImmutable('2026-01-01')]);
        $newer = ConversationFactory::createOne(['customer' => $customer, 'updatedAt' => new \DateTimeImmutable('2026-03-01')]);

        $results = $this->repository->findByCustomer($customer->_real());

        $this->assertCount(2, $results);
        $this->assertSame($newer->getId()->toRfc4122(), $results[0]->getId()->toRfc4122());
    }

    public function testFindByHostReturnsHostConversations(): void
    {
        $hostProfile = HostProfileFactory::createOne();
        $host = $hostProfile->getUser();

        ConversationFactory::createOne(['host' => $host]);
        ConversationFactory::createOne(['host' => $host]);

        $results = $this->repository->findByHost($host);

        $this->assertCount(2, $results);
    }

    public function testFindByHostExcludesOtherHosts(): void
    {
        $hostProfile1 = HostProfileFactory::createOne();
        $hostProfile2 = HostProfileFactory::createOne();

        ConversationFactory::createOne(['host' => $hostProfile1->getUser()]);
        ConversationFactory::createOne(['host' => $hostProfile2->getUser()]);

        $results = $this->repository->findByHost($hostProfile1->getUser());

        $this->assertCount(1, $results);
    }

    public function testFindByCustomerReturnsEmptyWhenNoConversations(): void
    {
        $customer = UserFactory::createOne(['roles' => ['ROLE_CUSTOMER']]);

        $results = $this->repository->findByCustomer($customer->_real());

        $this->assertCount(0, $results);
    }
}
