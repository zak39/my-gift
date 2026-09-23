<?php

namespace App\Twig\Components\Organism\GiftListHeader;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Actions
{
    public User $user;

    /** True on the owner's own management page, false on a public profile. */
    public bool $ownerView = false;
}
