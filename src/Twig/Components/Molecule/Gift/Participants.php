<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Entity\User;
use App\Enum\GiftStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Participants
{
    public Gift $gift;

    public function __construct(private Security $security)
    {
    }

    /**
     * The creator may see who is giving, except while a pot is still filling up:
     * `contributing` means the collected amount has not reached the price yet.
     */
    public function showContributors(): bool
    {
        if ($this->gift->getParticipants()->isEmpty()) {
            return false;
        }

        $hiddenFromCreator = $this->security->isGranted('gift.is_creator', $this->gift)
            && GiftStatus::CONTRIBUTING === $this->gift->getStatus()
        ;

        return !$hiddenFromCreator;
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
