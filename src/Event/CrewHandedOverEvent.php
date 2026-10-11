<?php

namespace App\Event;

use App\Entity\Crew;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A crew taken over by another member, its creator deleting their account and leaving it.
 */
final class CrewHandedOverEvent extends Event
{
    public function __construct(
        private readonly Crew $crew,
        private readonly User $previousCreator,
    ) {
    }

    /** Already under its new creator (Crew::getCreatedBy()), the previous one out of it. */
    public function getCrew(): Crew
    {
        return $this->crew;
    }

    /** About to be deleted: only their name is safe to read. */
    public function getPreviousCreator(): User
    {
        return $this->previousCreator;
    }
}
