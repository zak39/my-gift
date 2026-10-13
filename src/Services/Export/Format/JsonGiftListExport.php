<?php

namespace App\Services\Export\Format;

use App\Services\Dto\ExportedListDto;
use App\Strategy\GiftListExportFormat;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The gift list as JSON, for whoever reuses it elsewhere: the price stays a number.
 */
#[AsTaggedItem(index: 'json')]
final class JsonGiftListExport implements GiftListExportFormat
{
    public function contentType(): string
    {
        return 'application/json';
    }

    /**
     * No header to write: each gift carries its keys. A list of lists, not an object keyed
     * by their names, which two crews may share.
     */
    public function write(array $columns, array $lists, string $path): void
    {
        $data = array_map(
            fn (ExportedListDto $list) => [
                'Origine' => $list->name,
                // A date as ISO 8601, not json_encode()'s object of date and timezone.
                'Cadeaux' => array_map(
                    fn (array $row) => array_map(
                        fn (string|float|int|\DateTimeImmutable|null $value) => $value instanceof \DateTimeImmutable ? $value->format('Y-m-d') : $value,
                        $row,
                    ),
                    $list->rows,
                ),
            ],
            $lists,
        );

        file_put_contents($path, json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR));
    }
}
