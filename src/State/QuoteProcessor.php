<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\QuoteRequest;
use App\Repository\LodgingRepository;
use App\Service\PriceCalculator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuoteProcessor implements ProcessorInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private PriceCalculator $priceCalculator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof QuoteRequest);

        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $seasons = $lodging->getSeasons()->toArray();
        $priceOverrides = $lodging->getPriceOverrides()->toArray();

        return $this->priceCalculator->calculate(
            $lodging,
            $data->checkin,
            $data->checkout,
            $data->guestsCount,
            $seasons,
            $priceOverrides,
        );
    }
}
