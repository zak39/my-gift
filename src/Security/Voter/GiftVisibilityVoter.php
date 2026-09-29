<?php

namespace App\Security\Voter;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Who may see a gift at all. Its creator always does, drafts included. Anyone
 * else only sees the gifts the creator shares with their friends: public, filed
 * under a section, and the viewer passes `user.can_see_gifts` on the creator.
 * The favourites query applies the same friendship rule.
 *
 * Guards the routes that open a gift or engage with it by its id, so a hidden
 * gift can't be opened, favourited, reserved or contributed to by guessing its
 * URL. Routes that back out (unfavourite, disengage, cancel a contribution) stay
 * open: hiding a gift must not trap someone already engaged with it.
 */
final class GiftVisibilityVoter extends Voter
{
    public function __construct(
        private AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // https://symfony.com/doc/current/security/voters.html
        return 'gift.can_see' === $attribute
            && $subject instanceof Gift
        ;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /** @var Gift $subject */
        if ($user === $subject->getCreatedBy()) {
            return true;
        }

        // An ended friendship must not trap a participant either: they still open the
        // gift to review or change their share, not only to back out.
        if ($user instanceof User && null !== $subject->getParticipant($user)) {
            return true;
        }

        return
            $subject->isPublic()
            && null !== $subject->getSection()
            && $this->accessDecisionManager->decide($token, ['user.can_see_gifts'], $subject->getCreatedBy())
        ;
    }
}
