<?php

namespace App\Event;

use App\Entity\Gift;
use Symfony\Contracts\EventDispatcher\Event;

final class GiftPriceChangedEvent extends Event
{
    public function __construct(
        private readonly Gift $gift,
        private readonly float $previousPrice,
    ) {
    }

    public function getGift(): Gift
    {
        return $this->gift;
    }

    public function getPreviousPrice(): float
    {
        return $this->previousPrice;
    }
}
