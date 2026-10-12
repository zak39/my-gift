<?php

namespace App\Services\Participation;

use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Services\Lifecycle\GiftLifecycle;
use App\Strategy\ParticipationReleaseStrategy;

/**
 * A reservation: released, as if its giver had withdrawn it. The gift is on offer again
 * and its owner told.
 */
final class ReservationRelease implements ParticipationReleaseStrategy
{
    public function __construct(
        private readonly GiftLifecycle $giftLifecycle,
    ) {
    }

    public function supports(Participant $participation): bool
    {
        return GiftStatus::RESERVED === $participation->getGift()?->getStatus();
    }

    public function release(Participant $participation): void
    {
        $this->giftLifecycle->disengage($participation->getGift(), $participation->getParticipantUser());
    }
}
