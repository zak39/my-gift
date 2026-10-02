<?php

namespace App\Security\Voter;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may see a gift at all. Its owners always do, drafts included: its creator, or
 * every member of the crew whose list it is filed in. Anyone else only sees the gifts
 * shared with friends: public, filed under a section, and the viewer may see that
 * list, `user.can_see_gifts` on the creator of a personal list, `crew.can_see_list`
 * on a crew. The favourites query applies the same friendship rule.
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

        if (!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /** @var Gift $subject */
        if ($subject->isOwnedBy($user)) {
            return true;
        }

        // An ended friendship must not trap a participant either: they still open the
        // gift to review or change their share, not only to back out.
        if (null !== $subject->getParticipant($user)) {
            return true;
        }

        if (!$subject->isPublic() || null === $subject->getSection()) {
            return false;
        }

        $crew = $subject->getCrew();

        return null !== $crew
            ? $this->accessDecisionManager->decide($token, ['crew.can_see_list'], $crew)
            : $this->accessDecisionManager->decide($token, ['user.can_see_gifts'], $subject->getCreatedBy())
        ;
    }
}
