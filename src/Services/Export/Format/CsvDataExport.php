<?php

namespace App\Services\Export\Format;

use App\Services\Dto\ExportedDatasetDto;
use App\Strategy\DataExportFormat;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * The user's data as CSV files, to open in a spreadsheet: Excel in French as well as
 * LibreOffice. A CSV holds a single table, so one file per dataset in a ZIP archive, as
 * profil.csv or cadeaux.csv.
 */
#[AsTaggedItem(index: 'csv')]
final class CsvDataExport implements DataExportFormat
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function contentType(): string
    {
        return 'application/zip';
    }

    public function extension(): string
    {
        return 'zip';
    }

    public function write(array $datasets, string $path): void
    {
        $zip = new \ZipArchive();
        // Overwritten: the empty file ExportAccountData created is no archive yet.
        if (true !== $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            throw new \RuntimeException(\sprintf('The CSV export cannot be written to "%s".', $path));
        }

        foreach ($datasets as $dataset) {
            $zip->addFromString($this->slugger->slug($dataset->name)->lower().'.csv', $this->csv($dataset));
        }

        if (!$zip->close()) {
            throw new \RuntimeException(\sprintf('The CSV export cannot be written to "%s".', $path));
        }
    }

    /**
     * A dataset split into lists, as the gifts, has them one after the other, each line
     * saying its list's owner in a first column.
     */
    private function csv(ExportedDatasetDto $dataset): string
    {
        $stream = fopen('php://temp', 'w+');
        if (false === $stream) {
            throw new \RuntimeException('No temporary stream could be opened for the CSV export.');
        }

        fwrite($stream, "\xEF\xBB\xBF"); // BOM for UTF-8

        $split = null !== $dataset->ownerColumn;
        fputcsv($stream, [...($split ? [$dataset->ownerColumn] : []), ...$dataset->columns], ';', escape: '');

        foreach ($dataset->lists as $list) {
            $ownerCell = $split ? [$list->owner] : [];
            foreach ($list->rows as $row) {
                fputcsv($stream, array_map($this->cell(...), [...$ownerCell, ...array_values($row)]), ';', escape: '');
            }
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if (false === $csv) {
            throw new \RuntimeException(\sprintf('The "%s" CSV of the export cannot be read back.', $dataset->name));
        }

        return $csv;
    }

    private function cell(string|float|int|\DateTimeImmutable|null $value): string|int|null
    {
        return match (true) {
            // The floats are money, the price as the pool's amount: French-style decimals.
            \is_float($value) => number_format($value, 2, ',', ''),
            $value instanceof \DateTimeImmutable => $value->format('Y-m-d'),
            // Text a spreadsheet would run as a formula, as a gift's title typed by any crew
            // member may be: the leading quote keeps it text. A lone "-" runs nothing.
            \is_string($value) && mb_strlen($value) > 1 && str_contains("=+-@\t\r", $value[0]) => "'".$value,
            default => $value,
        };
    }
}
