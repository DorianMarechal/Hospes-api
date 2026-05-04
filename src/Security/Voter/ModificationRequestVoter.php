<?php

namespace App\Security\Voter;

use App\Entity\BookingModificationRequest;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/** @extends Voter<string, BookingModificationRequest> */
final class ModificationRequestVoter extends Voter
{
    public const VIEW = 'MODIFICATION_VIEW';
    public const RESPOND = 'MODIFICATION_RESPOND';

    public function __construct(
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::RESPOND])
            && $subject instanceof BookingModificationRequest;
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

        $booking = $subject->getBooking();
        if (null === $booking) {
            return false;
        }

        $isCustomer = null !== $booking->getCustomer()?->getId()
            && $booking->getCustomer()->getId()->equals($user->getId());

        $isHost = null !== $booking->getLodging()?->getHost()?->getId()
            && $booking->getLodging()->getHost()->getId()->equals($user->getHostProfile()?->getId());

        if (self::VIEW === $attribute) {
            return $isCustomer || $isHost;
        }

        // RESPOND: seul l'autre partie (pas le demandeur) peut accepter/rejeter
        if (self::RESPOND === $attribute) {
            $requestedBy = $subject->getRequestedBy();
            if (null === $requestedBy) {
                return false;
            }

            $isRequester = $requestedBy->getId()?->equals($user->getId());

            return ($isCustomer || $isHost) && !$isRequester;
        }

        return false;
    }
}
