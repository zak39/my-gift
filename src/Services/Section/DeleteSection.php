<?php

namespace App\Services\Section;

use App\Entity\Section;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Deletes a section but not its gifts: out of any section, each goes back to its author's
 * « Cadeaux sans section », leaving a crew's list for a crew's section.
 */
final class DeleteSection
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Section $section): void
    {
        $section->releaseGifts();
        $this->entityManager->remove($section);
        $this->entityManager->flush();
    }
}
