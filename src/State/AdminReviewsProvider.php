<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ReviewRepository;

class AdminReviewsProvider implements ProviderInterface
{
    public function __construct(
        private ReviewRepository $reviewRepository,
    ) {
    }

    /**
     * @return \App\Entity\Review[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        return $this->reviewRepository->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
