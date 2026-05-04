<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Guidebook;
use App\Repository\GuidebookRepository;
use App\Repository\LodgingRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicGuidebookProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private GuidebookRepository $guidebookRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Guidebook
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (null === $lodging) {
            throw new NotFoundHttpException('Lodging not found.');
        }

        return $this->guidebookRepository->findByLodging($lodging);
    }
}
