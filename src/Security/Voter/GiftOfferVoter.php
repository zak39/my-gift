<?php

namespace App\Security\Voter;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may start engaging with a gift, by reserving it or chipping in: someone who sees it,
 * neither owns it nor takes part in it yet. Its owners never offer it; someone already
 * taking part manages their share instead, even once the gift became theirs by joining
 * its crew.
 */
final class GiftOfferVoter extends Voter
{
    public function __construct(
        private AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return 'gift.can_offer' === $attribute
            && $subject instanceof Gift
        ;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /* @var Gift $subject */
        return !$subject->getOwnership()->includes($user)
            && null === $subject->getParticipant($user)
            && $this->accessDecisionManager->decide($token, ['gift.can_see'], $subject)
        ;
    }
}
