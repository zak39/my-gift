<?php

namespace App\Twig\Components\Molecule\User;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The avatar of the profile page, with its reset button: one block, so that the Turbo Stream
 * sent after a save swaps both, the button and its modal depending on the avatar.
 */
#[AsTwigComponent]
final class ProfileAvatar
{
    public User $user;
}
