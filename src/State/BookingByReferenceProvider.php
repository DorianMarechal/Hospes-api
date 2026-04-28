<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BookingRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingByReferenceProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $filters = $context['filters'] ?? [];
        $reference = $filters['reference'] ?? null;

        if (!$reference) {
            throw new BadRequestHttpException('The "reference" query parameter is required');
        }

        $booking = $this->bookingRepository->findByReference($reference);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        return [$booking];
    }
}
