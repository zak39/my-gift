<?php

namespace App\Twig\Components\Molecule\User;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Stands in for the face of a user without an avatar (User::getAvatarField()). Fills the
 * frame of Molecule:UserAvatar, which sizes it.
 */
#[AsTwigComponent]
final class AvatarPlaceholder
{
    public string $alt;
}
