<?php

namespace App\Security\Voter;

use App\Entity\CrewInvitation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may apply the transitions of the `crew_invitation` workflow, asked by its guards.
 */
final class CrewInvitationVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, ['crew_invitation.answer', 'crew_invitation.cancel'], true)
            && $subject instanceof CrewInvitation
        ;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        /* @var CrewInvitation $subject */
        return match ($attribute) {
            // Accept or refuse.
            'crew_invitation.answer' => $user === $subject->getInvitee(),
            // Any member, the inviter or not: an invitation sent by mistake is the crew's business.
            'crew_invitation.cancel' => $subject->getCrew()->hasMember($user),
        };
    }
}
