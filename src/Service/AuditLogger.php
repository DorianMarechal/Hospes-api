<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function log(string $action, string $entityType, Uuid $entityId, array $payload = []): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $log = new AuditLog();
        $log->setAdmin($user);
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setPayload($payload);
        $log->setIpAddress($this->requestStack->getCurrentRequest()?->getClientIp());
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($log);
    }
}
