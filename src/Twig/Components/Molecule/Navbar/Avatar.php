<?php

namespace App\Twig\Components\Molecule\Navbar;

use App\Entity\User;
use App\Repository\CrewInvitationRepository;
use App\Repository\FriendRequestRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Avatar
{
    public function __construct(
        private Security $security,
        private FriendRequestRepository $friendRequestRepository,
        private CrewInvitationRepository $crewInvitationRepository,
    ) {
    }

    public function getPendingFriendRequestCount(): int
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $this->friendRequestRepository->countPendingReceivedBy($user) : 0;
    }

    public function getPendingCrewInvitationCount(): int
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $this->crewInvitationRepository->countPendingReceivedBy($user) : 0;
    }
}
