<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Deposit;
use App\Repository\BookingRepository;
use App\Repository\DepositRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class BookingDepositProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private DepositRepository $depositRepository,
        private Security $security,
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Deposit
    {
        $booking = $this->bookingRepository->find($uriVariables['bookingId']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());
        $isAdmin = \in_array('ROLE_ADMIN', $reachableRoles, true);

        if (!$isAdmin) {
            $isCustomer = $booking->getCustomer()?->getId()?->equals($user->getId());
            $lodgingHost = $booking->getLodging()?->getHost();
            $isHost = null !== $lodgingHost && $lodgingHost->getId()?->equals($user->getHostProfile()?->getId());

            if (!$isCustomer && !$isHost) {
                throw new HttpException(403, 'Access denied');
            }
        }

        $deposit = $this->depositRepository->findByBooking($booking);
        if (!$deposit) {
            throw new NotFoundHttpException('No deposit found for this booking');
        }

        return $deposit;
    }
}
