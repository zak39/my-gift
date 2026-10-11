<?php

namespace App\Event;

use App\Entity\Gift;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A gift about to go with its owner's account: dispatched before its deletion, while its
 * participants can still be read.
 */
final class GiftDeletedWithAccountEvent extends Event
{
    public function __construct(
        private readonly Gift $gift,
    ) {
    }

    public function getGift(): Gift
    {
        return $this->gift;
    }
}
