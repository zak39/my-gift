<?php

namespace App\Services;

use App\Entity\Gift;
use App\Entity\Section;
use App\Entity\User;

class FavoriteGiftGrouper
{
    /**
     * The favourite gifts of $user, grouped by the friend who offers them, then by section.
     *
     * A gift does not have to belong to a section, so the ones without land in
     * `giftsWithoutSection` and the page renders them on their own under the friend.
     *
     * Everything comes back as lists: the template only ever iterates, and entity ids
     * as array keys made Twig walk an extra level for nothing.
     *
     * @return list<array{
     *     user: User,
     *     sections: list<array{section: Section, gifts: list<Gift>}>,
     *     giftsWithoutSection: list<Gift>,
     * }>
     */
    public function groupedByCreator(User $user): array
    {
        $grouped = [];

        foreach ($user->getFavoriteGifts() as $gift) {
            $createdBy = $gift->getCreatedBy();
            $creatorId = $createdBy->getId();
            $section = $gift->getSection();

            $grouped[$creatorId] ??= [
                'user' => $createdBy,
                'sections' => [],
                'giftsWithoutSection' => [],
            ];

            if (null === $section) {
                $grouped[$creatorId]['giftsWithoutSection'][] = $gift;

                continue;
            }

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
