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
        return $this->reviewRepository->findAll();
    }
}
