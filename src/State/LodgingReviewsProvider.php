<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\LodgingRepository;
use App\Repository\ReviewRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LodgingReviewsProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private ReviewRepository $reviewRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);

        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        return $this->reviewRepository->findByLodging($lodging);
    }
}
