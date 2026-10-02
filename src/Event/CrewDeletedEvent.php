<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Carries what the crew no longer can once deleted: its name, its creator and its members.
 */
final class CrewDeletedEvent extends Event
{
    /**
     * @param list<User> $members all of them, the creator included
     */
    public function __construct(
        private readonly string $crewName,
        private readonly User $creator,
        private readonly array $members,
    ) {
    }

    public function getCrewName(): string
    {
        return $this->crewName;
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    /**
     * @return list<User>
     */
    public function getMembers(): array
    {
        return $this->members;
    }
}
