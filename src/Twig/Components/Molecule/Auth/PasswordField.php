<?php

namespace App\Twig\Components\Molecule\Auth;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The password field of the login forms (the login card, ReauthenticateCard), posted as
 * _password to form_login, with its show-password button.
 */
#[AsTwigComponent]
final class PasswordField
{
    /** Off by default: on the login card, the username field above has the focus. */
    public bool $autofocus = false;
}
