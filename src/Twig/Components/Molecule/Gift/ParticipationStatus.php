<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ParticipationStatus
{
    public ?Gift $gift = null;
    public string $avatarSize = 'size-8';

    public function __construct(private Security $security)
    {
    }

    public function getFirstParticipant(): ?Participant
    {
        return $this->gift->getParticipants()->first() ?: null;
    }

    public function isCurrentUser(?User $user): bool
    {
        /** @var User */
        $currentUser = $this->security->getUser();

        return $user && $currentUser && $user->getId() === $currentUser->getId();
    }
}
