<?php

namespace App\Services\Section;

use App\Entity\Section;
use App\Event\CrewGiftDeletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Empties a section about to go: each gift goes back to its author, among their « Cadeaux
 * sans section », leaving a crew's list for a crew's section.
 *
 * A crew gift whose author deleted their account has nobody to go back to: it goes with
 * the section, its givers told. Still filed in it when removed, so that Vich finds its
 * image where GiftDirectoryNamer put it, under the crew.
 *
 * No flush: the caller removes the section, or the whole crew, on the same one.
 */
final class ReleaseGifts
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(Section $section, bool $crewDeleted = false): void
    {
        foreach ($section->getGifts()->toArray() as $gift) {
            if (null !== $gift->getCreatedBy()) {
                $section->removeGift($gift);

                continue;
            }

            $this->eventDispatcher->dispatch(new CrewGiftDeletedEvent($gift, $section->getCrew()->getName(), $crewDeleted));
            $this->entityManager->remove($gift);
        }
    }
}
