<?php

namespace App\Services\Dto;

/**
 * One list of the export, the user's own or a crew's: a sheet of a spreadsheet. A value,
 * not an array key: two crews may share a name.
 */
final class ExportedListDto
{
    /**
     * @param list<array<string, string|float|int|\DateTimeImmutable|null>> $rows one per gift, keyed by column title
     */
    public function __construct(
        public readonly string $name,
        public readonly array $rows,
    ) {
    }
}
