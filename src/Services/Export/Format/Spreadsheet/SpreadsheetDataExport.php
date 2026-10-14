<?php

namespace App\Services\Export\Format\Spreadsheet;

use App\Strategy\DataExportFormat;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AbstractWriterMultiSheets;

/**
 * The user's data as a spreadsheet, one sheet per list of each dataset: « Profil », « Mes
 * cadeaux », one per crew's list, « Participations »… What the ODS and XLSX formats share.
 * Their cells are typed, so the price is a number and the date a date, whatever the
 * spreadsheet's language.
 */
abstract class SpreadsheetDataExport implements DataExportFormat
{
    /**
     * OpenSpout's writer of the format.
     */
    abstract protected function createWriter(): AbstractWriterMultiSheets;

    public function write(array $datasets, string $path): void
    {
        $sheetNames = new SheetNames();
        $dateStyle = (new Style())->withFormat('dd/mm/yyyy');

        $writer = $this->createWriter();
        $writer->openToFile($path);

        try {
            $first = true;
            foreach ($datasets as $dataset) {
                foreach ($dataset->lists as $list) {
                    // The workbook opens with a first sheet already.
                    $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                    $first = false;
                    $sheet->setName($sheetNames->claim($list->name));

                    $writer->addRow(Row::fromValues($dataset->columns));

                    foreach ($list->rows as $row) {
                        // Typed by hand, not by Row::fromValues(): a title starting with "="
                        // would become a formula, run when the file is opened.
                        $writer->addRow(new Row(array_map(
                            fn (string|float|int|\DateTimeImmutable|null $value) => match (true) {
                                null === $value => new Cell\EmptyCell(null),
                                \is_float($value), \is_int($value) => new Cell\NumericCell($value),
                                $value instanceof \DateTimeImmutable => new Cell\DateTimeCell($value, $dateStyle),
                                default => new Cell\StringCell($value),
                            },
                            array_values($row),
                        )));
                    }
                }
            }
        } catch (\Throwable $exception) {
            // Closed all the same, so that the writer's own temporary files go too. A failure
            // there would only hide the first one.
            try {
                $writer->close();
            } catch (\Throwable) {
            }

            throw $exception;
        }

        $writer->close();
    }
}
