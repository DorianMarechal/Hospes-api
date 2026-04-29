<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Tests\Factory\NotificationFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class NotificationRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private NotificationRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(NotificationRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testFindByUserReturnsNotificationsForThatUser(): void
    {
        $user = UserFactory::createOne();
        $otherUser = UserFactory::createOne();

        NotificationFactory::createOne(['user' => $user]);
        NotificationFactory::createOne(['user' => $user]);
        NotificationFactory::createOne(['user' => $otherUser]);

        $results = $this->repository->findByUser($user->_real());

        $this->assertCount(2, $results);
    }

    public function testFindByUserReturnsOrderedByCreatedAtDesc(): void
    {
        $user = UserFactory::createOne();

        NotificationFactory::createOne([
            'user' => $user,
            'createdAt' => new \DateTimeImmutable('2026-01-01'),
            'title' => 'Old',
        ]);
        NotificationFactory::createOne([
            'user' => $user,
            'createdAt' => new \DateTimeImmutable('2026-03-01'),
            'title' => 'Recent',
        ]);

        $results = $this->repository->findByUser($user->_real());

        $this->assertSame('Recent', $results[0]->getTitle());
    }

    public function testMarkAllReadMarksUnreadNotifications(): void
    {
        $user = UserFactory::createOne();

        NotificationFactory::createOne(['user' => $user, 'isRead' => false]);
        NotificationFactory::createOne(['user' => $user, 'isRead' => false]);
        NotificationFactory::createOne(['user' => $user, 'isRead' => true, 'readAt' => new \DateTimeImmutable()]);

        $this->repository->markAllRead($user->_real());
        $this->em->clear();

        $notifications = $this->repository->findByUser($user->_real());
        $unread = array_filter($notifications, fn (Notification $n) => !$n->isRead());

        $this->assertCount(0, $unread);
        $this->assertCount(3, $notifications);
    }

    public function testMarkAllReadDoesNotAffectOtherUsers(): void
    {
        $user = UserFactory::createOne();
        $otherUser = UserFactory::createOne();

        NotificationFactory::createOne(['user' => $user, 'isRead' => false]);
        NotificationFactory::createOne(['user' => $otherUser, 'isRead' => false]);

        $this->repository->markAllRead($user->_real());
        $this->em->clear();

        $otherNotifs = $this->repository->findByUser($otherUser->_real());
        $this->assertCount(1, $otherNotifs);
        $this->assertFalse($otherNotifs[0]->isRead());
    }

    public function testMarkAllReadSetsReadAtTimestamp(): void
    {
        $user = UserFactory::createOne();

        NotificationFactory::createOne(['user' => $user, 'isRead' => false]);

        $this->repository->markAllRead($user->_real());
        $this->em->clear();

        $notifications = $this->repository->findByUser($user->_real());
        $this->assertNotNull($notifications[0]->getReadAt());
        // Verify readAt is recent (within last 10 seconds)
        $diff = (new \DateTimeImmutable())->getTimestamp() - $notifications[0]->getReadAt()->getTimestamp();
        $this->assertLessThan(10, $diff);
    }
}
