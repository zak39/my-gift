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
     * Every gift must belong to a section: callers
     * filter out the others (see `GiftRepository::findFavoritesGifts()`).
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
     *     sections: list<array{section: \App\Entity\Section, gifts: list<Gift>}>,
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
                'name' => null !== $crew ? $crew->getName() : $gift->getCreatedBy()->getFullname(),
                'sections' => [],
            ];

            $sectionId = $section->getId();
            $grouped[$listKey]['sections'][$sectionId] ??= [
                'section' => $section,
                'gifts' => [],
            ];
            $grouped[$listKey]['sections'][$sectionId]['gifts'][] = $gift;
        }

        // Section ids served only to group; the template just iterates.
        foreach ($grouped as $listKey => $group) {
            $grouped[$listKey]['sections'] = array_values($group['sections']);
        }

        return array_values($grouped);
    }
}
