<?php

namespace App\Services;

use App\Entity\Gift;

class FavoriteGiftGrouper
{
    /**
     * Gifts grouped by the friend who offers them, then by section.
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
     *     user: \App\Entity\User,
     *     sections: list<array{section: \App\Entity\Section, gifts: list<Gift>}>,
     * }>
     */
    public function groupedByCreator(iterable $gifts): array
    {
        $grouped = [];

        foreach ($gifts as $gift) {
            $createdBy = $gift->getCreatedBy();
            $creatorId = $createdBy->getId();
            $section = $gift->getSection();

            $grouped[$creatorId] ??= [
                'user' => $createdBy,
                'sections' => [],
            ];

            $sectionId = $section->getId();
            $grouped[$creatorId]['sections'][$sectionId] ??= [
                'section' => $section,
                'gifts' => [],
            ];
            $grouped[$creatorId]['sections'][$sectionId]['gifts'][] = $gift;
        }

        // Section ids served only to group; the template just iterates.
        foreach ($grouped as $creatorId => $group) {
            $grouped[$creatorId]['sections'] = array_values($group['sections']);
        }

        return array_values($grouped);
    }
}
