<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Carries what the crew no longer can once deleted: its name, its owner, who deleted it,
 * and its members.
 */
final class CrewDeletedEvent extends Event
{
    /**
     * @param list<User> $members all of them, the owner included
     */
    public function __construct(
        private readonly string $crewName,
        private readonly User $owner,
        private readonly array $members,
    ) {
    }

    public function getCrewName(): string
    {
        return $this->crewName;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @return list<User>
     */
    public function getMembers(): array
    {
        return $this->members;
    }
}
