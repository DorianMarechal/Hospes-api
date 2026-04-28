<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BookingRepository;

class AdminBookingsProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
    ) {
    }

    /**
     * @return \App\Entity\Booking[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->bookingRepository->findAll();
    }
}
