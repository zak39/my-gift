<?php

namespace App\Event;

use App\Entity\Crew;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class CrewMemberLeftEvent extends Event
{
    public function __construct(
        private readonly Crew $crew,
        private readonly User $member,
        private readonly bool $accountDeleted = false,
    ) {
    }

    public function getCrew(): Crew
    {
        return $this->crew;
    }

    /** The member who left: no longer among the crew's members. */
    public function getMember(): User
    {
        return $this->member;
    }

    /** Left because they deleted their account: their profile and avatar are gone. */
    public function isAccountDeleted(): bool
    {
        return $this->accountDeleted;
    }
}
