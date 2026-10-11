<?php

namespace App\Services\Participation;

use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Services\Lifecycle\GiftLifecycle;
use App\Strategy\ParticipationReleaseStrategy;

/**
 * A contribution to a pool still open: taken out of it, the other contributors told. The
 * last one leaving puts the gift back on offer, without telling the user who is gone.
 */
final class PoolContributionRelease implements ParticipationReleaseStrategy
{
    public function __construct(
        private readonly GiftLifecycle $giftLifecycle,
    ) {
    }

    public function supports(Participant $participation): bool
    {
        return GiftStatus::CONTRIBUTING === $participation->getGift()?->getStatus();
    }

    public function release(Participant $participation): void
    {
        $this->giftLifecycle->cancelledContribution($participation->getGift(), $participation->getParticipantUser(), accountDeleted: true);
    }
}
