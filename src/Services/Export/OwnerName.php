<?php

namespace App\Services\Export;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\Section;
use App\Entity\User;

/**
 * Whose list a gift or a section is part of, as the « Propriétaire » columns say:
 * « Moi », « Groupe … » for a crew's, its author's name for another user's.
 */
final class OwnerName
{
    public static function me(): string
    {
        return 'Moi';
    }

    public static function crew(Crew $crew): string
    {
        return ListName::crew($crew);
    }

    /**
     * Its crew when filed in one, or else its author, as GiftOwnership tells.
     */
    public static function of(Gift|Section $item, User $viewer): string
    {
        $author = $item->getCreatedBy();

        return match (true) {
            null !== $item->getCrew() => self::crew($item->getCrew()),
            $author === $viewer => self::me(),
            default => $author->getFullname(),
        };
    }
}
