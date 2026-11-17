<?php

namespace App\Services;

use App\Entity\Gift;

class FavoriteGiftGrouper
{
    /**
     * Gifts grouped by the list they are part of, then by section: a crew's gifts under
     * their crew, whoever added them (their author may even be gone), any other under the
     * friend who offers it.
     *
     * A friend's gifts filed in no section come under a null section: a crew's gift always
     * has one.
     *
     * Everything comes back as lists: the template only ever iterates, and entity ids
     * as array keys made Twig walk an extra level for nothing.
     *
     * @param iterable<Gift> $gifts
     *
     * @return list<array{
     *     crew: ?\App\Entity\Crew,
     *     user: ?\App\Entity\User,
     *     name: string,
     *     sections: list<array{section: ?\App\Entity\Section, gifts: list<Gift>}>,
     * }>
     */
    public function groupedByList(iterable $gifts): array
    {
        $grouped = [];

        foreach ($gifts as $gift) {
            $crew = $gift->getCrew();
            $section = $gift->getSection();
            // One key space for both kinds of list: a crew and a user may share an id.
            $listKey = null !== $crew ? 'crew-'.$crew->getId() : 'user-'.$gift->getCreatedBy()->getId();

            $grouped[$listKey] ??= [
                'crew' => $crew,
                'user' => null !== $crew ? null : $gift->getCreatedBy(),
                'name' => null !== $crew ? $crew->getName() : $gift->getCreatedBy()->getPseudo(),
                'sections' => [],
            ];

            // A string key for the gifts filed in no section: no section id can take it.
            $sectionId = null !== $section ? $section->getId() : 'none';
            $grouped[$listKey]['sections'][$sectionId] ??= [
                'section' => $section,
                'gifts' => [],
            ];
            $grouped[$listKey]['sections'][$sectionId]['gifts'][] = $gift;
        }

        // Section ids served only to group; the template just iterates. The gifts filed in
        // no section come last, as on the friend's own page (uksort keeps the others' order).
        foreach ($grouped as $listKey => $group) {
            uksort($group['sections'], static fn (int|string $a, int|string $b) => ('none' === $a) <=> ('none' === $b));
            $grouped[$listKey]['sections'] = array_values($group['sections']);
        }

        return array_values($grouped);
    }
}
