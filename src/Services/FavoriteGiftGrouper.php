<?php

namespace App\Services;

use App\Entity\User;

class FavoriteGiftGrouper
{
    public function groupedByCreator(User $user): array
    {
        $grouped = [];

        foreach ($user->getFavoriteGifts() as $gift) {
            $createdBy = $gift->getCreatedBy();
            $section = $gift->getSection();

            $grouped[$createdBy->getId()]['user'] ??= $createdBy;
            $grouped[$createdBy->getId()]['sections'][$section->getId()]['section'] ??= $section;
            $grouped[$createdBy->getId()]['sections'][$section->getId()]['gifts'][] = $gift;
        }

        return $grouped;
    }
}
