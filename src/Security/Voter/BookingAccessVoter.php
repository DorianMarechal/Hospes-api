<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/** @extends Voter<string, Booking> */
final class BookingAccessVoter extends Voter
{
    public const VIEW = 'BOOKING_VIEW';
    public const EDIT = 'BOOKING_EDIT';
    public const CANCEL = 'BOOKING_CANCEL';

    public function __construct(
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::CANCEL])
            && $subject instanceof Booking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());
        if (\in_array('ROLE_ADMIN', $reachableRoles, true)) {
            return true;
        }

        // Le customer propriétaire de la réservation
        if (null !== $subject->getCustomer()?->getId() && $subject->getCustomer()->getId()->equals($user->getId())) {
            return true;
        }

        // L'hôte propriétaire du logement
        $lodgingHost = $subject->getLodging()?->getHost();
        if (null !== $lodgingHost?->getId() && $lodgingHost->getId()->equals($user->getHostProfile()?->getId())) {
            return true;
        }

        return false;
    }
}
