<?php

namespace App\Services\Participation;

use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Services\Lifecycle\GiftLifecycle;
use App\Strategy\ParticipationReleaseStrategy;

/**
 * A reservation its giver declared ready: not given yet, so released all the same. The
 * gift is on offer again and its owner told, as for a reservation.
 */
final class ReadyReservationRelease implements ParticipationReleaseStrategy
{
    public function __construct(
        private readonly GiftLifecycle $giftLifecycle,
    ) {
    }

    public function supports(Participant $participation): bool
    {
        $gift = $participation->getGift();

        return GiftStatus::READY === $gift?->getStatus() && !$gift->getPool()->isPooled();
    }

    public function release(Participant $participation): void
    {
        $this->giftLifecycle->release($participation->getGift(), $participation->getParticipantUser());
    }
}
