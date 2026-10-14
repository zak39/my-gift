<?php

namespace App\Strategy;

use App\Services\Dto\ExportedDatasetDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A file format the user's data can be downloaded in. One class per format, under
 * src/Services/Export/Format/, indexed by its name in the URL with
 * #[AsTaggedItem(index: 'csv')]: ExportAccountData picks it from there, so a new format
 * needs nothing else but its button on the profile page.
 *
 * It only formats: the data comes from AccountData, the same for every format.
 */
#[AutoconfigureTag]
interface DataExportFormat
{
    public function contentType(): string;

    /**
     * The extension of the file written, which is not always the format's name: the CSV
     * files come in a ZIP archive.
     */
    public function extension(): string;

    /**
     * Writes the file at $path, which ExportAccountData created empty and sends once written.
     *
     * @param list<ExportedDatasetDto> $datasets in the order of AccountData, the profile first
     */
    public function write(array $datasets, string $path): void;
}
