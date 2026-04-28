<?php

namespace App\Security\Voter;

use App\Entity\StaffAssignment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class StaffVoter extends Voter
{
    public const STAFF_MANAGE = 'STAFF_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::STAFF_MANAGE === $attribute && $subject instanceof StaffAssignment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        \assert($subject instanceof StaffAssignment);

        // Only the host who created the assignment can manage it
        return $subject->getHost()?->getId()?->equals($user->getId()) ?? false;
    }
}
