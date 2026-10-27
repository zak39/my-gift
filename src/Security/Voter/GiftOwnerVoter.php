<?php

namespace App\Security\Voter;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * The owners of a gift manage it and stand on the receiving side: its creator, or every
 * member of the crew whose list it is filed in (Gift::getOwnership()).
 *
 * `gift.can_claim`: a crew member taking over a crew gift whose author deleted their
 * account (ClaimGift).
 */
final class GiftOwnerVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, ['gift.is_owner', 'gift.can_claim'], true)
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

        /* @var Gift $subject */
        return match ($attribute) {
            'gift.is_owner' => $subject->getOwnership()->includes($user),
            'gift.can_claim' => $subject->getOwnership()->canBeClaimedBy($user),
        };
    }
}
