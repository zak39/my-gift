<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Participants
{
    public Gift $gift;

    public function __construct(private Security $security)
    {
    }

    public function showContributors(): bool
    {
        return !$this->gift->getParticipants()->isEmpty()
            && $this->security->isGranted('gift.can_see_contributors', $this->gift)
        ;
    }

    /**
     * Telling you that you already gave is about you, so it leaks nothing.
     */
    public function showOwnParticipation(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof User
            && null !== $this->gift->getParticipant($user)
            && $this->gift->getStatus()?->hasReachedContributing()
        ;
    }
}
