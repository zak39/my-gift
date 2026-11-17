<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Whether a page addressed by a user id is that very user's: on /users/{id}/sections, the
 * owner previewing their own public page gets its owner actions
 * (Organism:GiftListHeader:Actions) and the hint on its gifts filed in no section
 * (Molecule:Gift:UnfiledHint).
 */
final class UserOwnerVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return 'user.is_owner' === $attribute
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /** @var User $subject */
        // Compare ids rather than instances: the subject may come from another
        // source than the identity map that refreshed the logged-in user.
        if ($user->getId() !== $subject->getId()) {
            $vote?->addReason('This page belongs to another user.');

            return false;
        }

        return true;
    }
}
