<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\LodgingRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SeasonCollectionProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);

        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        return $lodging->getSeasons()->toArray();
    }
}
