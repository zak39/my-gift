<?php

namespace App\Security;

use App\Entity\User;
use App\Security\Exception\UnverifiedEmailException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Keeps an account whose email address was never confirmed from logging in (EmailVerifier):
 * otherwise anyone could sign up with someone else's address and act under their name.
 * Checked by every authenticator of the main firewall, the login form as the remember-me.
 *
 * Its message is shown as is on the login page, which links to a new confirmation email
 * (RegistrationController::resendEmailConfirmation()).
 */
final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    /**
     * Once the password was right: refused before, the message would tell anyone typing an
     * address that it has an account awaiting confirmation.
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new UnverifiedEmailException('Confirmez votre adresse e-mail avec le lien reçu dans votre boîte de réception au moment de votre inscription. Pas reçu ? Demandez-en un nouveau sous le formulaire.');
        }
    }
}
