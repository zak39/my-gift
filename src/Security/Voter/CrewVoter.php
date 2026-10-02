<?php

namespace App\Security\Voter;

use App\Entity\Crew;
use App\Entity\User;
use App\Repository\FriendBookRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * What a user may do with a crew. Its members are equals: each of them runs its list,
 * invites their friends and renames it. Only the creator stands apart: they cannot leave
 * the crew, and they alone delete it. `crew.can_see_list` plays for a crew's list the
 * part `user.can_see_gifts` plays for a personal one: GiftVisibilityVoter asks it.
 */
final class CrewVoter extends Voter
{
    public function __construct(
        private FriendBookRepository $friendBookRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, ['crew.is_member', 'crew.can_leave', 'crew.can_delete', 'crew.can_see_list'], true)
            && $subject instanceof Crew
        ;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /* @var Crew $subject */
        return match ($attribute) {
            'crew.is_member' => $subject->hasMember($user),
            'crew.can_leave' => $subject->hasMember($user) && $user !== $subject->getCreatedBy(),
            'crew.can_delete' => $user === $subject->getCreatedBy(),
            'crew.can_see_list' => $this->canSeeList($subject, $user),
        };
    }

    /**
     * The members, and the friends of at least one member: a couple's friends all see
     * the list of their child, even those who only know one of the parents.
     */
    private function canSeeList(Crew $crew, User $user): bool
    {
        if ($crew->hasMember($user)) {
            return true;
        }

        $members = $crew->getMembers();
        $book = $this->friendBookRepository->of($user);

        foreach ($members as $member) {
            if ($book->hasFriend($member)) {
                return true;
            }
        }

        return false;
    }
}
