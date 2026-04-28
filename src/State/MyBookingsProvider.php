<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BookingRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MyBookingsProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        return $this->bookingRepository->findByCustomer($user);
    }
}
