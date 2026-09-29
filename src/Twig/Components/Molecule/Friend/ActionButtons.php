<?php

namespace App\Twig\Components\Molecule\Friend;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The friendship buttons for one person, from the logged-in user's side: add, answer
 * a request, cancel one, remove, block or unblock.
 */
#[AsTwigComponent]
final class ActionButtons
{
    /** The other person. */
    public User $user;
}
