<?php

namespace App\Services\Export\Format\Spreadsheet;

use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\ODS\Writer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The gift lists as an OpenDocument spreadsheet, for LibreOffice: no import dialog asks
 * for a separator, as with the CSV.
 */
#[AsTaggedItem(index: 'ods')]
final class OdsGiftListExport extends SpreadsheetGiftListExport
{
    public function contentType(): string
    {
        return 'application/vnd.oasis.opendocument.spreadsheet';
    }

    protected function createWriter(): AbstractWriterMultiSheets
    {
        return new Writer();
    }
}
