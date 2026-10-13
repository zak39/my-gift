<?php

namespace App\Services\Export\Format\Spreadsheet;

use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The gift lists as an Excel workbook.
 */
#[AsTaggedItem(index: 'xlsx')]
final class XlsxGiftListExport extends SpreadsheetGiftListExport
{
    public function contentType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    protected function createWriter(): AbstractWriterMultiSheets
    {
        return new Writer();
    }
}
