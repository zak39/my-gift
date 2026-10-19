<?php

namespace App\Event;

use App\Entity\Crew;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A crew taken over by another member: passed on by its owner, or by its owner deleting
 * their account and leaving it.
 */
final class CrewHandedOverEvent extends Event
{
    public function __construct(
        private readonly Crew $crew,
        private readonly User $previousOwner,
        private readonly bool $accountDeleted,
    ) {
    }

    /** Already under its new owner (Crew::getOwner()). */
    public function getCrew(): Crew
    {
        return $this->crew;
    }

    /**
     * Still a member when they passed the crew on; out of it, and about to be deleted
     * (only their name is safe to read), when they deleted their account.
     */
    public function getPreviousOwner(): User
    {
        return $this->previousOwner;
    }

    public function isAccountDeleted(): bool
    {
        return $this->accountDeleted;
    }
}
