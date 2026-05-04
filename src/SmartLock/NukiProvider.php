<?php

namespace App\SmartLock;

use Psr\Log\LoggerInterface;

class NukiProvider implements SmartLockProviderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function generateCode(string $lockId, \DateTimeImmutable $validFrom, \DateTimeImmutable $validTo): string
    {
        $this->logger->info('Nuki generateCode - not implemented (requires Nuki Web API access)');

        return '';
    }

    public function revokeCode(string $lockId, string $code): void
    {
        $this->logger->info('Nuki revokeCode - not implemented');
    }

    public function listCodes(string $lockId): array
    {
        $this->logger->info('Nuki listCodes - not implemented');

        return [];
    }
}
