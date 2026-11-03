<?php

namespace App\Event;

use App\Entity\FriendRequest;
use Symfony\Contracts\EventDispatcher\Event;

final class FriendRequestSentEvent extends Event
{
    public function __construct(
        private readonly FriendRequest $friendRequest,
    ) {
    }

    public function getFriendRequest(): FriendRequest
    {
        return $this->friendRequest;
    }
}
