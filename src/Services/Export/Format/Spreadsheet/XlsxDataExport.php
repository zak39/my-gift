<?php

namespace App\Services\Export\Format\Spreadsheet;

use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The user's data as an Excel workbook.
 */
#[AsTaggedItem(index: 'xlsx')]
final class XlsxDataExport extends SpreadsheetDataExport
{
    public function contentType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function extension(): string
    {
        return 'xlsx';
    }

    protected function createWriter(): AbstractWriterMultiSheets
    {
        return new Writer();
    }
}
