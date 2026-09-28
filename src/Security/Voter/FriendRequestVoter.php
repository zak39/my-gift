<?php

namespace App\Security\Voter;

use App\Entity\FriendRequest;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may apply the transitions of the `friend_request` workflow, asked by its guards.
 */
final class FriendRequestVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, ['friend_request.answer', 'friend_request.cancel'], true)
            && $subject instanceof FriendRequest
        ;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /* @var FriendRequest $subject */
        return match ($attribute) {
            // Accept or refuse.
            'friend_request.answer' => $user === $subject->getRecipient(),
            'friend_request.cancel' => $user === $subject->getSender(),
        };
    }
}
