<?php

namespace App\Strategy;

use App\Services\Dto\ExportedListDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A file format the gift lists can be downloaded in. One class per format, under
 * src/Services/Export/Format/, indexed by its extension with #[AsTaggedItem(index: 'csv')]:
 * ExportGiftList picks it from the URL, so a new format needs nothing else but its
 * button on the profile page.
 *
 * It only formats: the lists come from GiftLists, the same for every format.
 */
#[AutoconfigureTag]
interface GiftListExportFormat
{
    public function contentType(): string;

    /**
     * Writes the file at $path, which ExportGiftList created empty and sends once written.
     *
     * @param list<string>          $columns the column titles, given even without any row
     * @param list<ExportedListDto> $lists   the user's own list first, then their crews'
     */
    public function write(array $columns, array $lists, string $path): void;
}
