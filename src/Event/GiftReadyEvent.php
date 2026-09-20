<?php

namespace App\Event;

use App\Entity\Gift;
use Symfony\Contracts\EventDispatcher\Event;

final class GiftReadyEvent extends Event
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
