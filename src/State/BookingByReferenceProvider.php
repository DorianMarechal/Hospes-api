<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\BookingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingByReferenceProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $filters = $context['filters'] ?? [];
        $reference = $filters['reference'] ?? null;

        if (!$reference) {
            throw new BadRequestHttpException('The "reference" query parameter is required');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new HttpException(401, 'Authentication required');
        }

        $booking = $this->bookingRepository->findByReference($reference);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        $isCustomer = $booking->getCustomer()?->getId()?->equals($user->getId());
        $isHost = $booking->getLodging()?->getHost()?->getUser()?->getId()?->equals($user->getId());
        $isAdmin = \in_array('ROLE_ADMIN', $user->getRoles(), true);

        if (!$isCustomer && !$isHost && !$isAdmin) {
            throw new NotFoundHttpException('Booking not found');
        }

        return [$booking];
    }
}
