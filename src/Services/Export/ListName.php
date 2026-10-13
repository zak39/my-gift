<?php

namespace App\Services\Export;

use App\Entity\Crew;

/**
 * The name the export gives the user's gift lists, as the profile shows them: the sheets
 * of a spreadsheet, the « Origine » of the JSON.
 */
final class ListName
{
    public static function own(): string
    {
        return 'Mes cadeaux';
    }

    public static function crew(Crew $crew): string
    {
        return 'Groupe '.$crew->getName();
    }
}
