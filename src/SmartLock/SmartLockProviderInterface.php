<?php

namespace App\SmartLock;

interface SmartLockProviderInterface
{
    public function generateCode(string $lockId, \DateTimeImmutable $validFrom, \DateTimeImmutable $validTo): string;

    public function revokeCode(string $lockId, string $code): void;

    /**
     * @return array{code: string, valid_from: string, valid_to: string}[]
     */
    public function listCodes(string $lockId): array;
}
