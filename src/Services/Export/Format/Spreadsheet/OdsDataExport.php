<?php

namespace App\Services\Export\Format\Spreadsheet;

use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\ODS\Writer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The user's data as an OpenDocument spreadsheet, for LibreOffice: no import dialog asks
 * for a separator, as with the CSV.
 */
#[AsTaggedItem(index: 'ods')]
final class OdsDataExport extends SpreadsheetDataExport
{
    public function contentType(): string
    {
        return 'application/vnd.oasis.opendocument.spreadsheet';
    }

    public function extension(): string
    {
        return 'ods';
    }

    protected function createWriter(): AbstractWriterMultiSheets
    {
        return new Writer();
    }
}
