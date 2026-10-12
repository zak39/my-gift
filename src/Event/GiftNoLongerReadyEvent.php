<?php

namespace App\Event;

use App\Entity\Gift;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A pool declared ready lost a contributor, gone with their account: reopened, or back on
 * offer when nobody is left in it.
 */
final class GiftNoLongerReadyEvent extends Event
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
