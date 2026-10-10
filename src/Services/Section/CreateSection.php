<?php

namespace App\Services\Section;

use App\Entity\Section;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Saves a new section, in the user's own list (Me\SectionController) or in a crew's
 * (Crew\SectionController).
 */
final class CreateSection
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function __invoke(Section $section): void
    {
        $section->setSlug($this->slugger->slug($section->getTitle())->lower()->toString());

        $this->entityManager->persist($section);
        $this->entityManager->flush();
    }
}
