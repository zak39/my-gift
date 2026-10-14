<?php

namespace App\Services\Export\Format;

use App\Services\Dto\ExportedListDto;
use App\Strategy\DataExportFormat;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The user's data as JSON, for whoever reuses it elsewhere: the price stays a number.
 */
#[AsTaggedItem(index: 'json')]
final class JsonDataExport implements DataExportFormat
{
    public function contentType(): string
    {
        return 'application/json';
    }

    public function extension(): string
    {
        return 'json';
    }

    /**
     * An object keyed by dataset, « Profil », « Cadeaux »…, whose names are ours, so
     * unique. A dataset split into lists is a list of { Origine, <dataset> }, each list by
     * its name as a spreadsheet's sheet, not an object keyed by their names, which two
     * crews may share.
     */
    public function write(array $datasets, string $path): void
    {
        $data = [];
        foreach ($datasets as $dataset) {
            $data[$dataset->name] = null !== $dataset->ownerColumn
                ? array_map(
                    fn (ExportedListDto $list) => ['Origine' => $list->name, $dataset->name => $this->rows($list)],
                    $dataset->lists,
                )
                : $this->rows($dataset->lists[0])
            ;
        }

        file_put_contents($path, json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR));
    }

    /**
     * No header to write: each row carries its keys. A date as ISO 8601, not
     * json_encode()'s object of date and timezone.
     *
     * @return list<array<string, string|float|int|null>>
     */
    private function rows(ExportedListDto $list): array
    {
        return array_map(
            fn (array $row) => array_map(
                fn (string|float|int|\DateTimeImmutable|null $value) => $value instanceof \DateTimeImmutable ? $value->format('Y-m-d') : $value,
                $row,
            ),
            $list->rows,
        );
    }
}
