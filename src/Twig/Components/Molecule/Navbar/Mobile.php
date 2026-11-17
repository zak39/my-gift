<?php

namespace App\Twig\Components\Molecule\Navbar;

use App\Entity\User;
use App\Repository\CrewInvitationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Mobile
{
    public function __construct(
        private Security $security,
        private CrewInvitationRepository $crewInvitationRepository,
    ) {
    }

    public function getPendingCrewInvitationCount(): int
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $this->crewInvitationRepository->countPendingReceivedBy($user) : 0;
    }
}
