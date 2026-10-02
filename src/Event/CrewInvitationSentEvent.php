<?php

namespace App\Event;

use App\Entity\CrewInvitation;
use Symfony\Contracts\EventDispatcher\Event;

final class CrewInvitationSentEvent extends Event
{
    public function __construct(
        private readonly CrewInvitation $invitation,
    ) {
    }

    public function getInvitation(): CrewInvitation
    {
        return $this->invitation;
    }
}
