<?php

namespace App\Security\Voter;

use App\Entity\Gift;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Who may see a gift at all. Its creator always does, drafts included. Anyone
 * else only sees the gifts the creator shares: public and filed under a section,
 * the same rule the public list page and the favourites apply to their queries.
 *
 * Guards the routes that open a gift or engage with it by its id, so a hidden
 * gift can't be opened, favourited, reserved or contributed to by guessing its
 * URL. Routes that back out (unfavourite, disengage, cancel a contribution) stay
 * open: hiding a gift must not trap someone already engaged with it.
 */
final class GiftVisibilityVoter extends Voter
{
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

        return
            $subject->isPublic()
            && null !== $subject->getSection()
        ;
    }
}
