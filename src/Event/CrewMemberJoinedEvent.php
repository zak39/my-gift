<?php

namespace App\Event;

use App\Entity\Crew;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class CrewMemberJoinedEvent extends Event
{
    public function __construct(
        private readonly Crew $crew,
        private readonly User $member,
    ) {
    }

    public function getCrew(): Crew
    {
        return $this->crew;
    }

    /** The newcomer, already among the crew's members. */
    public function getMember(): User
    {
        return $this->member;
    }
}
