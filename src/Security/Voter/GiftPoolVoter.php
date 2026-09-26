<?php

namespace App\Security\Voter;

use App\Entity\Gift;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Who may see what about a gift's pool. The rule is the surprise: the person
 * being given to must not learn what has been collected on their own gift, and
 * discovers who is giving only once the pool is complete.
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
        if (!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /** @var Gift $subject */
        if ($user !== $subject->getCreatedBy()) {
            return true;
        }

        $vote?->addReason('The creator of a gift does not get to see its pool.');

        // A reservation, or a pool a contributor declared ready, holds no surprise left.
        return 'gift.can_see_contributors' === $attribute
            && !$subject->getStatus()?->isPoolOpen()
        ;
    }
}
