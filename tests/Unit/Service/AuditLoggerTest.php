<?php

namespace App\Tests\Unit\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class AuditLoggerTest extends TestCase
{
    private EntityManagerInterface $em;
    private Security $security;
    private RequestStack $requestStack;
    private AuditLogger $logger;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->logger = new AuditLogger($this->em, $this->security, $this->requestStack);
    }

    private function createRequest(string $ip = '127.0.0.1'): Request
    {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);

        return $request;
    }

    // --- happy path ---

    public function testLogPersistsAuditLogWithCorrectFields(): void
    {
        $user = new User();
        $entityId = Uuid::v7();

        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->method('getCurrentRequest')->willReturn($this->createRequest('192.168.1.10'));

        $persisted = null;
        $this->em
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (AuditLog $log) use (&$persisted) {
                $persisted = $log;
            });

        $this->logger->log('user.ban', 'User', $entityId, ['reason' => 'spam']);

        $this->assertInstanceOf(AuditLog::class, $persisted);
        $this->assertSame($user, $persisted->getAdmin());
        $this->assertSame('user.ban', $persisted->getAction());
        $this->assertSame('User', $persisted->getEntityType());
        $this->assertSame($entityId, $persisted->getEntityId());
        $this->assertSame(['reason' => 'spam'], $persisted->getPayload());
        $this->assertSame('192.168.1.10', $persisted->getIpAddress());
        $this->assertInstanceOf(\DateTimeImmutable::class, $persisted->getCreatedAt());
    }

    public function testLogPersistsAuditLogWithEmptyPayloadByDefault(): void
    {
        $user = new User();
        $entityId = Uuid::v7();

        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->method('getCurrentRequest')->willReturn($this->createRequest());

        $persisted = null;
        $this->em->method('persist')->willReturnCallback(function (AuditLog $log) use (&$persisted) {
            $persisted = $log;
        });

        $this->logger->log('lodging.delete', 'Lodging', $entityId);

        $this->assertSame([], $persisted->getPayload());
    }

    public function testLogSetsCreatedAtAsDateTimeImmutable(): void
    {
        $user = new User();
        $entityId = Uuid::v7();

        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->method('getCurrentRequest')->willReturn($this->createRequest());

        $before = new \DateTimeImmutable();

        $persisted = null;
        $this->em->method('persist')->willReturnCallback(function (AuditLog $log) use (&$persisted) {
            $persisted = $log;
        });

        $this->logger->log('booking.confirm', 'Booking', $entityId);

        $after = new \DateTimeImmutable();

        $createdAt = $persisted->getCreatedAt();
        $this->assertGreaterThanOrEqual($before, $createdAt);
        $this->assertLessThanOrEqual($after, $createdAt);
    }

    // --- null IP when no current request ---

    public function testLogPersistsWithNullIpWhenNoCurrentRequest(): void
    {
        $user = new User();
        $entityId = Uuid::v7();

        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $persisted = null;
        $this->em->method('persist')->willReturnCallback(function (AuditLog $log) use (&$persisted) {
            $persisted = $log;
        });

        $this->logger->log('test.action', 'Entity', $entityId);

        $this->assertNull($persisted->getIpAddress());
    }

    // --- non-User security user skips ---

    public function testLogWithNonUserSecurityUserDoesNotPersist(): void
    {
        $nonAppUser = $this->createStub(UserInterface::class);

        $this->security->method('getUser')->willReturn($nonAppUser);

        $this->em->expects($this->never())->method('persist');

        $this->logger->log('action', 'Entity', Uuid::v7());
    }

    // --- null security user skips ---

    public function testLogWithNullSecurityUserDoesNotPersist(): void
    {
        $this->security->method('getUser')->willReturn(null);

        $this->em->expects($this->never())->method('persist');

        $this->logger->log('action', 'Entity', Uuid::v7());
    }
}
