<?php

namespace App\Services\Participation;

use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Strategy\ParticipationReleaseStrategy;

/**
 * A gift given or archived: it never reopens, so its history stays, the participation
 * kept without anyone behind it (« Utilisateur supprimé »). Saved by the deletion's last
 * flush.
 */
final class GivenGiftRelease implements ParticipationReleaseStrategy
{
    public function supports(Participant $participation): bool
    {
        $status = $participation->getGift()?->getStatus();

        return GiftStatus::GIVEN === $status || GiftStatus::ARCHIVED === $status;
    }

    public function release(Participant $participation): void
    {
        $participation->detachDeletedUser();
    }
}
