<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Entity\Lodging;
use App\Entity\User;

/** @extends Voter<string, Lodging> */
final class LodgingVoter extends Voter
{
    public const EDIT   = 'LODGING_EDIT';
    public const VIEW   = 'LODGING_VIEW';
    public const DELETE = 'LODGING_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Lodging;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        switch ($attribute) {
            case self::VIEW:

                return true;

            case self::EDIT:
            case self::DELETE:
                // if the user is anonymous, do not grant access
                if (!$user instanceof User) {
                    $vote?->addReason('The user must be logged in to access this resource.');

                    return false;
                }

                if(in_array('ROLE_ADMIN', $user->getRoles())){
                    return true;
                }

                if($subject->getHost()->getId() === $user->getHostProfile()?->getId()){
                    return true;
                }

                return false;
        }

        return false;
    }
}
