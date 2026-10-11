<?php

namespace App\Services\Participation;

use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Strategy\ParticipationReleaseStrategy;
use Doctrine\ORM\EntityManagerInterface;

/**
 * A participation no workflow leaves behind: on a gift on offer, or on no gift at all (the
 * column allows it). A leftover, deleted rather than blocking the account's deletion, on
 * its last flush.
 */
final class LeftoverRelease implements ParticipationReleaseStrategy
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Participant $participation): bool
    {
        $gift = $participation->getGift();

        return null === $gift || GiftStatus::CREATED === $gift->getStatus();
    }

    public function release(Participant $participation): void
    {
        $gift = $participation->getGift();

        if (null === $gift) {
            $this->entityManager->remove($participation);

            return;
        }

        // orphanRemoval deletes it.
        $gift->removeParticipant($participation);
    }
}
