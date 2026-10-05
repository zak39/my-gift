<?php

namespace App\Services\Section;

use App\Entity\Section;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Saves a new section, in the user's own list (Me\SectionController) or in a crew's
 * (Crew\SectionController).
 */
final class CreateSection
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Section $section): void
    {
        $section->setSlug(strtolower(str_replace(' ', '-', $section->getTitle())));

        $this->entityManager->persist($section);
        $this->entityManager->flush();
    }
}
