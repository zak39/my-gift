<?php

namespace App\Security\Voter;

use App\Entity\Section;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may rename, delete or fill a section: its creator, or every member of the crew
 * whose list it is part of (Section::isOwnedBy()).
 */
final class SectionOwnerVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return 'section.is_owner' === $attribute
            && $subject instanceof Section
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

        /* @var Section $subject */
        return $subject->isOwnedBy($user);
    }
}
