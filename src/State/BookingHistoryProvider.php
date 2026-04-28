<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BookingRepository;
use App\Repository\BookingStatusHistoryRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingHistoryProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private BookingStatusHistoryRepository $historyRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $booking = $this->bookingRepository->find($uriVariables['id']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        return $this->historyRepository->findBy(['booking' => $booking], ['createdAt' => 'ASC']);
    }
}
