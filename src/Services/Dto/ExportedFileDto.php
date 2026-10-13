<?php

namespace App\Services\Dto;

/**
 * The export written to a temporary file, ready to be sent: what the controller needs to
 * answer with it. Deleting the file once sent is up to the one who sends it.
 */
final class ExportedFileDto
{
    public function __construct(
        public readonly string $path,
        public readonly string $contentType,
        public readonly string $filename,
    ) {
    }
}
