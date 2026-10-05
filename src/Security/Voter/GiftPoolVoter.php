<?php

namespace App\Security\Voter;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may see what about a gift's pool. The rule is the surprise: the people being
 * given to, the gift's owners (its creator, or every member of its crew), must not
 * learn what has been collected, and discover who is giving only once the pool is
 * complete.
 */
final class GiftPoolVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, ['gift.can_see_amounts', 'gift.can_see_contributors'], true)
            && $subject instanceof Gift
        ;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /** @var Gift $subject */
        // Someone paying into the pool has nothing left to discover, owner or not: a
        // contributor who joined the gift's crew afterwards.
        if (!$subject->getOwnership()->includes($user) || null !== $subject->getParticipant($user)) {
            return true;
        }

        $vote?->addReason('The owners of a gift do not get to see its pool.');

        // A reservation, or a pool a contributor declared ready, holds no surprise left.
        return 'gift.can_see_contributors' === $attribute
            && !$subject->getStatus()?->isPoolOpen()
        ;
    }
}
