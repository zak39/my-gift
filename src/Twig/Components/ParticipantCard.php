<?php

namespace App\Twig\Components;

use App\Entity\Participant;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('molecule:ParticipantCard')]
final class ParticipantCard
{
    public ?Participant $participant = null;
    public bool $showAmount = false;
    public string $avatarSize = 'size-8';

    public function __construct(
        private Security $security,
    ) {
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
