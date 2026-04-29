<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthCheckController
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[Route('/api/health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $dbOk = false;
        try {
            $this->connection->executeQuery('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {
        }

        $status = $dbOk ? 'ok' : 'degraded';
        $code = $dbOk ? 200 : 503;

        return new JsonResponse([
            'status' => $status,
            'database' => $dbOk,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ], $code);
    }
}
