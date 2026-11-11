<?php

namespace App\Twig\Components\Molecule\Navbar;

use App\Entity\User;
use App\Repository\FriendRequestRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The links both navbars share (Molecule:Navbar:Web and Molecule:Navbar:Mobile).
 */
#[AsTwigComponent]
final class Links
{
    public function __construct(
        private Security $security,
        private FriendRequestRepository $friendRequestRepository,
    ) {
    }

    public function getPendingFriendRequestCount(): int
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $this->friendRequestRepository->countPendingReceivedBy($user) : 0;
    }
}
