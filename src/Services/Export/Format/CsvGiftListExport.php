<?php

namespace App\Services\Export\Format;

use App\Strategy\GiftListExportFormat;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The gift list as CSV, to open in a spreadsheet: Excel in French as well as LibreOffice.
 */
#[AsTaggedItem(index: 'csv')]
final class CsvGiftListExport implements GiftListExportFormat
{
    public function contentType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    /**
     * A CSV has a single sheet: the lists one after the other, each line saying its own.
     */
    public function write(array $columns, array $lists, string $path): void
    {
        $stream = fopen($path, 'w');
        if (false === $stream) {
            throw new \RuntimeException(\sprintf('The CSV export cannot be written to "%s".', $path));
        }

        fwrite($stream, "\xEF\xBB\xBF"); // BOM for UTF-8

        fputcsv($stream, ['Origine', ...$columns], ';', escape: '');

        foreach ($lists as $list) {
            foreach ($list->rows as $row) {
                fputcsv($stream, array_map($this->cell(...), [$list->name, ...array_values($row)]), ';', escape: '');
            }
        }

        fclose($stream);
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
