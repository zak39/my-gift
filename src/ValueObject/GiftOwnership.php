<?php

namespace App\ValueObject;

use App\Entity\Crew;
use App\Entity\User;

/**
 * Who a gift belongs to: the members of its crew when it is filed in a crew's list, or
 * else the user who added it. Not persisted: Gift::getOwnership() builds it on demand,
 * so filing the gift elsewhere changes its owners at once.
 */
final class GiftOwnership
{
    public function __construct(
        private readonly ?User $creator,
        private readonly ?Crew $crew,
    ) {
    }

    public function includes(User $user): bool
    {
        return null !== $this->crew
            ? $this->crew->hasMember($user)
            : $this->creator === $user
        ;
    }

    /**
     * Who they are: the crew's members, or its creator. None for a gift out of a crew that
     * lost its creator, a state NotNullOutOfCrew only keeps forms from making.
     *
     * @return list<User>
     */
    public function owners(): array
    {
        if (null !== $this->crew) {
            return $this->crew->getMembers();
        }

        return null !== $this->creator ? [$this->creator] : [];
    }
}
