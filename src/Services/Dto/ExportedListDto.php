<?php

namespace App\Services\Dto;

/**
 * One list of a dataset of the export, as the user's own gifts or a crew's: a sheet of a
 * spreadsheet, by its name. A value, not an array key: two crews may share a name.
 */
final class ExportedListDto
{
    /**
     * @param list<array<string, string|float|int|\DateTimeImmutable|null>> $rows  keyed by column title
     * @param ?string                                                       $owner whose list it is, « Moi », « Groupe … », when its dataset is split
     */
    public function __construct(
        public readonly string $name,
        public readonly array $rows,
        public readonly ?string $owner = null,
    ) {
    }
}
