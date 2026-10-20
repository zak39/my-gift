<?php

namespace App\Event;

use App\Entity\Gift;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A crew gift whose author deleted their account, gone with its section or its crew: it
 * had nobody to go back to. Dispatched before its deletion, while its participants can
 * still be read.
 */
final class CrewGiftDeletedEvent extends Event
{
    public function __construct(
        private readonly Gift $gift,
        private readonly string $crewName,
        private readonly bool $crewDeleted,
    ) {
    }

    public function getGift(): Gift
    {
        return $this->gift;
    }

    public function getCrewName(): string
    {
        return $this->crewName;
    }

    /** The whole crew deleted, rather than one of its sections. */
    public function isCrewDeleted(): bool
    {
        return $this->crewDeleted;
    }
}
