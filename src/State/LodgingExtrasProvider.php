<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ExtraRepository;
use App\Repository\LodgingRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LodgingExtrasProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private ExtraRepository $extraRepository,
    ) {
    }

    /**
     * @return \App\Entity\Extra[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (null === $lodging) {
            throw new NotFoundHttpException('Lodging not found.');
        }

        return $this->extraRepository->findEnabledByLodging($lodging);
    }
}
