<?php

namespace App\Twig\Components\Molecule\Navbar;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Avatar
{
    public function __construct(private Security $security)
    {
    }

    public function getPendingFriendRequestCount(): int
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user->getReceivedFriendRequests()->count() : 0;
    }
}
