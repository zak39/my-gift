<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Carries what the account no longer can once deleted: where and to whom to confirm it.
 */
final class AccountDeletedEvent extends Event
{
    public function __construct(
        private readonly string $email,
        private readonly string $firstname,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }
}
