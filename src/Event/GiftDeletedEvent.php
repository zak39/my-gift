<?php

namespace App\Event;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A gift deleted by its owners from their list, theirs or their crew's (DeleteGift).
 * Dispatched before its deletion, under its lock, while its participants can still be read.
 */
final class GiftDeletedEvent extends Event
{
    public function __construct(
        private readonly Gift $gift,
        private readonly ?User $deletedBy,
    ) {
    }

    public function getGift(): Gift
    {
        return $this->gift;
    }

    /**
     * Its author for a gift of their own list, or the member of its crew who deleted it;
     * null when deleted from the admin.
     */
    public function getDeletedBy(): ?User
    {
        return $this->deletedBy;
    }
}
