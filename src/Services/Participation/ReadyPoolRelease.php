<?php

namespace App\Services\Participation;

use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Services\Lifecycle\GiftLifecycle;
use App\Strategy\ParticipationReleaseStrategy;

/**
 * A contribution to a pool declared ready: not given yet, so taken out of it. The pool
 * reopens for the others to declare it ready again, and its owner is told it no longer is.
 */
final class ReadyPoolRelease implements ParticipationReleaseStrategy
{
    public function __construct(
        private readonly GiftLifecycle $giftLifecycle,
    ) {
    }

    public function supports(Participant $participation): bool
    {
        $gift = $participation->getGift();

        return GiftStatus::READY === $gift?->getStatus() && $gift->getPool()->isPooled();
    }

    public function release(Participant $participation): void
    {
        $this->giftLifecycle->leaveReadyPool($participation->getGift(), $participation->getParticipantUser());
    }
}
