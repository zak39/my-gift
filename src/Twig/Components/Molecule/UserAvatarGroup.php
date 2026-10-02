<?php

namespace App\Twig\Components\Molecule;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Overlapping avatars, then a "+N" for the people beyond $max.
 */
#[AsTwigComponent]
final class UserAvatarGroup
{
    /** @var iterable<User> */
    public iterable $users = [];

    public int $max = 5;

    /** Tailwind utilities sizing each avatar and the "+N" bubble alike. */
    public string $sizeClass = 'w-10';
}
