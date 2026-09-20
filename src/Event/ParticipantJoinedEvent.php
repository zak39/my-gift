<?php

namespace App\Event;

use App\Entity\Gift;
use App\Entity\Participant;
use Symfony\Contracts\EventDispatcher\Event;

final class ParticipantJoinedEvent extends Event
{
    public function __construct(
        private readonly Gift $gift,
        private readonly Participant $participant,
    ) {
    }

    public function getGift(): Gift
    {
        return $this->gift;
    }

    public function getParticipant(): Participant
    {
        return $this->participant;
    }
}
