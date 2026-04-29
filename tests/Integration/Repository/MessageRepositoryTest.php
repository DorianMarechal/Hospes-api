<?php

namespace App\Tests\Integration\Repository;

use App\Repository\MessageRepository;
use App\Tests\Factory\ConversationFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private MessageRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(MessageRepository::class);
    }

    public function testFindByConversationReturnsMessages(): void
    {
        $conversation = ConversationFactory::createOne();
        $sender = UserFactory::createOne();

        MessageFactory::createOne(['conversation' => $conversation, 'sender' => $sender]);
        MessageFactory::createOne(['conversation' => $conversation, 'sender' => $sender]);

        $results = $this->repository->findByConversation($conversation->_real());

        $this->assertCount(2, $results);
    }

    public function testFindByConversationExcludesOtherConversations(): void
    {
        $conv1 = ConversationFactory::createOne();
        $conv2 = ConversationFactory::createOne();
        $sender = UserFactory::createOne();

        MessageFactory::createOne(['conversation' => $conv1, 'sender' => $sender]);
        MessageFactory::createOne(['conversation' => $conv2, 'sender' => $sender]);

        $results = $this->repository->findByConversation($conv1->_real());

        $this->assertCount(1, $results);
    }

    public function testFindByConversationOrderedByCreatedAtAsc(): void
    {
        $conversation = ConversationFactory::createOne();
        $sender = UserFactory::createOne();

        $older = MessageFactory::createOne(['conversation' => $conversation, 'sender' => $sender, 'createdAt' => new \DateTimeImmutable('2026-01-01')]);
        $newer = MessageFactory::createOne(['conversation' => $conversation, 'sender' => $sender, 'createdAt' => new \DateTimeImmutable('2026-03-01')]);

        $results = $this->repository->findByConversation($conversation->_real());

        $this->assertCount(2, $results);
        $this->assertSame($older->getId()->toRfc4122(), $results[0]->getId()->toRfc4122());
        $this->assertSame($newer->getId()->toRfc4122(), $results[1]->getId()->toRfc4122());
    }

    public function testFindByConversationReturnsEmptyWhenNoMessages(): void
    {
        $conversation = ConversationFactory::createOne();

        $results = $this->repository->findByConversation($conversation->_real());

        $this->assertCount(0, $results);
    }
}
