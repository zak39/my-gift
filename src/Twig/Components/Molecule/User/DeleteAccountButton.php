<?php

namespace App\Twig\Components\Molecule\User;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Deleting the user's account, once confirmed in a modal that says what becomes of their
 * gifts and crews, and with their password: an open session is not enough.
 */
#[AsTwigComponent]
final class DeleteAccountButton
{
    public User $user;
}
