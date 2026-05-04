<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\LodgingRepository;
use App\Repository\LodgingTranslationRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LodgingTranslationsProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private LodgingTranslationRepository $translationRepository,
    ) {
    }

    /**
     * @return \App\Entity\LodgingTranslation[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (null === $lodging) {
            throw new NotFoundHttpException('Lodging not found.');
        }

        return $this->translationRepository->findByLodging($lodging);
    }
}
