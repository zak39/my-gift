<?php

namespace App\Services\Section;

use App\Entity\Section;
use App\Services\PendingFileRemovals;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Deletes a section but not its gifts: out of any section, each goes back to its author's
 * « Cadeaux sans section », leaving a crew's list for a crew's section. A crew gift whose
 * author deleted their account has nobody to go back to: it goes with the section, its
 * givers told (ReleaseGifts).
 */
final class DeleteSection
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReleaseGifts $releaseGifts,
        private readonly PendingFileRemovals $pendingFileRemovals,
    ) {
    }

    public function __invoke(Section $section): void
    {
        $connection = $this->entityManager->getConnection();

        // One transaction: the givers' emails ReleaseGifts queues (Messenger's doctrine
        // transport) are rolled back with the deletion if it fails.
        $connection->transactional(function () use ($section): void {
            ($this->releaseGifts)($section);
            $this->entityManager->remove($section);
            $this->entityManager->flush();
        });

        // Committed: the moved images' originals can go (PendingFileRemovals). Inside a
        // wider transaction (an account deletion), its opener removes them after its own.
        if (!$connection->isTransactionActive()) {
            $this->pendingFileRemovals->removeAll();
        }
    }
}
