<?php

namespace App\Services\Export\Dataset;

use App\Entity\Section;
use App\Entity\User;
use App\Repository\SectionRepository;
use App\Services\Dto\ExportedDatasetDto;
use App\Services\Export\OwnerName;

/**
 * The sections of the lists the user runs, their own and their crews', whoever created
 * them: an empty section shows in no gift's row.
 */
final class SectionsDataset
{
    public function __construct(
        private readonly SectionRepository $sectionRepository,
    ) {
    }

    public function of(User $user): ExportedDatasetDto
    {
        // The user's own first, as their gifts are, then the crews' by name: PostgreSQL
        // sorts the sections without a crew last. usort() keeps the query's order otherwise.
        $sections = $this->sectionRepository->findManagedBy($user);
        usort($sections, fn (Section $a, Section $b) => (null !== $a->getCrew()) <=> (null !== $b->getCrew()));

        return ExportedDatasetDto::table('Sections', ['Propriétaire', 'Section', 'Description', 'Créée par'], array_map(
            fn (Section $section) => [
                'Propriétaire' => OwnerName::of($section, $user),
                'Section' => $section->getTitle(),
                'Description' => $section->getDescription(),
                // A crew section keeps no author once its user deleted their account.
                'Créée par' => $section->getCreatedBy()?->getPseudo() ?? 'Utilisateur supprimé',
            ],
            $sections,
        ));
    }
}
