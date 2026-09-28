<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may see a user's gifts: the user themselves and their friends, like a LinkedIn
 * connection. Named after what it grants rather than how it decides, so that a new
 * circle (family...) only changes this voter, not its callers.
 */
final class UserGiftsVisibilityVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return 'user.can_see_gifts' === $attribute
            && $subject instanceof User
        ;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('L\'utilisateur n\'est pas connecté.');

            return false;
        }

        /** @var User $subject */
        // Ids for the owner, as UserOwnerVoter does.
        if ($user->getId() === $subject->getId() || $user->hasFriend($subject)) {
            return true;
        }

        $vote?->addReason('Seuls les amis peuvent voir cette liste.');

        return false;
    }
}
