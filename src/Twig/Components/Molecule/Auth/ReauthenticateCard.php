<?php

namespace App\Twig\Components\Molecule\Auth;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The login page of a user remembered by its cookie, not fully logged in: a page asking for
 * IS_AUTHENTICATED_FULLY (the profile, the data export) sent them to it, or any access refused
 * to them (Symfony's ExceptionListener sends a remembered user to the login form). The account
 * is known: only the password is asked, and the login leads back to the page saved by the
 * firewall, if any.
 */
#[AsTwigComponent]
final class ReauthenticateCard
{
    public User $user;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing user would render a form with no account, with no error.
     */
    public function mount(User $user): void
    {
        $this->user = $user;
    }
}
