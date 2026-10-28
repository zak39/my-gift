<?php

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

/**
 * A login refused because the account's email address is not confirmed yet (UserChecker).
 * Its own class so that the login page tells it from the other errors: a warning rather
 * than an error, with the link to a new confirmation email (SecurityController::login()).
 * It reaches that page through the session, where it keeps its class.
 */
final class UnverifiedEmailException extends CustomUserMessageAccountStatusException
{
}
