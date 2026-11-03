<?php

namespace App\Twig\Components\Molecule;

use App\Entity\Participant;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ParticipantCard
{
    public Participant $participant;
    public bool $showAmount = false;
    public string $avatarSize = 'size-10';

    public function __construct(
        private Security $security,
    ) {
    }

    /**
     * Required through mount(): the template reads the participant throughout, so a missing
     * one would only fail further down, in getDisplayName(), with a 500, instead of a clear
     * error here.
     */
    public function mount(Participant $participant): void
    {
        $this->participant = $participant;
    }

    public function getDisplayName(): string
    {
        $name = $this->participant->getDisplayName();

        if ($this->isCurrentUser() && $this->participant->isAnonymous()) {
            $name .= ' (vous)';
        }

        return $name;
    }

    private function isCurrentUser(): bool
    {
        $user = $this->participant->getParticipantUser();
        /** @var \App\Entity\User */
        $currentUser = $this->security->getUser();

        return $user && $currentUser && $user->getId() === $currentUser->getId();
    }
}
