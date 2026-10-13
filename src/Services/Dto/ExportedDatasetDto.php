<?php

namespace App\Services\Dto;

/**
 * One kind of the user's data in the export, their profile, their gifts, their friends…:
 * a file of the CSV archive, a key of the JSON, one sheet or more of a spreadsheet.
 *
 * Either a table, a single list named as the dataset, or split into lists, as the gifts
 * are by the list they belong to: a spreadsheet then gives each list its own sheet, the
 * JSON names each list, and the CSV says each row's list owner under $ownerColumn.
 */
final class ExportedDatasetDto
{
    /**
     * @param list<string>          $columns     the column titles, the keys of every row, given even without any row
     * @param list<ExportedListDto> $lists
     * @param ?string               $ownerColumn the title of the column giving each row's list owner, null for a table
     */
    private function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly array $lists,
        public readonly ?string $ownerColumn,
    ) {
        // A header out of step would shift every cell under the wrong title.
        foreach ($lists as $list) {
            foreach ($list->rows as $row) {
                if ($columns !== array_keys($row)) {
                    throw new \LogicException(\sprintf('The rows of the "%s" dataset do not match its columns.', $name));
                }
            }
        }
    }

    /**
     * @param list<string>                                                  $columns
     * @param list<array<string, string|float|int|\DateTimeImmutable|null>> $rows    keyed by column title
     */
    public static function table(string $name, array $columns, array $rows): self
    {
        return new self($name, $columns, [new ExportedListDto($name, $rows)], null);
    }

    /**
     * @param list<string>          $columns
     * @param list<ExportedListDto> $lists
     */
    public static function split(string $name, string $ownerColumn, array $columns, array $lists): self
    {
        return new self($name, $columns, $lists, $ownerColumn);
    }
}
