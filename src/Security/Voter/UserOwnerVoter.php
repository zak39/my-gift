<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access to a page addressed by a user id only to that very user,
 * for routes such as /section/{id} where {id} is the owner of the list.
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
