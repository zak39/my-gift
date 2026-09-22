<?php

namespace App\Twig\Components\Organism\GiftListHeader;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Actions
{
    public User $user;

    /** True on the owner's own management page, false on a public profile. */
    public bool $ownerView = false;

    public function __construct(private Security $security)
    {
    }

    /**
     * Distinct from `ownerView`: the owner previewing their own public profile is
     * the owner, but is not in the management view.
     */
    public function isOwner(): bool
    {
        return $this->security->isGranted('user.is_owner', $this->user);
    }
}
