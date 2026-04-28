<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BookingRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingNightsProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $booking = $this->bookingRepository->find($uriVariables['id']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        return $booking->getBookingNights()->toArray();
    }
}
