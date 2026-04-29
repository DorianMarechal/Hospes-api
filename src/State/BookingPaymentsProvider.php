<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BookingRepository;
use App\Repository\PaymentRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class BookingPaymentsProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private PaymentRepository $paymentRepository,
        private Security $security,
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    /**
     * @return \App\Entity\Payment[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
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

        return $this->paymentRepository->findByBooking($booking);
    }
}
