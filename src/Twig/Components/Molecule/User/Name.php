<?php

namespace App\Twig\Components\Molecule\User;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * How a user is named to others: their pseudo, and under it, smaller, their full name for
 * whoever may see it (user.can_see_fullname: themselves and their friends). Inline, so
 * that it fits in a heading or a sentence; the caller's classes go on the pseudo's span.
 */
#[AsTwigComponent]
final class Name
{
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }
}
