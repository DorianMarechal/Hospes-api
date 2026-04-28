<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\LodgingRepository;

class AdminLodgingsProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
    ) {
    }

    /**
     * @return \App\Entity\Lodging[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->lodgingRepository->findAll();
    }
}
