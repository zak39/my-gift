<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Repository\FriendBookRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * What of a user others may see, granted to the user themselves and their friends, like a
 * LinkedIn connection:
 * - user.can_see_gifts: their sections and gifts;
 * - user.can_see_fullname: their full name next to their pseudo (a crew member who is no
 *   friend sees the pseudo only).
 *
 * One attribute per thing granted rather than per way of deciding, so that a new circle
 * (family...) or a rule of its own for one of them only changes this voter, not its callers.
 * Two queries hold the same circle and must follow it: the friends' search, on full names
 * too (UserRepository::searchFriends()), and the favourites (GiftRepository::findFavoritesGifts()).
 */
final class UserVisibilityVoter extends Voter
{
    private const REFUSALS = [
        'user.can_see_gifts' => 'Seuls les amis peuvent voir ces sections.',
        'user.can_see_fullname' => 'Seuls les amis voient le nom complet.',
    ];

    public function __construct(
        private FriendBookRepository $friendBookRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return isset(self::REFUSALS[$attribute])
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
        if ($user->getId() === $subject->getId() || $this->friendBookRepository->of($user)->hasFriend($subject)) {
            return true;
        }

        $vote?->addReason(self::REFUSALS[$attribute]);

        return false;
    }
}
