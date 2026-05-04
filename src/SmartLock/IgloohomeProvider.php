<?php

namespace App\SmartLock;

use Psr\Log\LoggerInterface;

class IgloohomeProvider implements SmartLockProviderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function generateCode(string $lockId, \DateTimeImmutable $validFrom, \DateTimeImmutable $validTo): string
    {
        $this->logger->info('Igloohome generateCode - not implemented (requires Igloohome API access)');

        return '';
    }

    public function revokeCode(string $lockId, string $code): void
    {
        $this->logger->info('Igloohome revokeCode - not implemented');
    }

    public function listCodes(string $lockId): array
    {
        $this->logger->info('Igloohome listCodes - not implemented');

        return [];
    }
}
