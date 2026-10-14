<?php

namespace App\Services\Export;

use App\Entity\User;
use App\Services\Dto\ExportedFileDto;
use App\Strategy\DataExportFormat;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

/**
 * The user's data written to a temporary file, in the format asked for: one of the
 * classes implementing DataExportFormat, under Format/, by its index. None for a format
 * none of them is.
 */
final class ExportAccountData
{
    public function __construct(
        #[AutowireLocator(DataExportFormat::class)]
        private readonly ContainerInterface $formats,
        private readonly AccountData $accountData,
    ) {
    }

    public function __invoke(User $user, string $format): ?ExportedFileDto
    {
        if (!$this->formats->has($format)) {
            return null;
        }

        /** @var DataExportFormat $writer */
        $writer = $this->formats->get($format);

        // A file rather than a string: a spreadsheet is a ZIP archive, which ZipArchive
        // writes to a real file only, and a file can be sent without being held in memory.
        $path = tempnam(sys_get_temp_dir(), 'data-export-');
        if (false === $path) {
            throw new \RuntimeException('No temporary file could be created for the data export.');
        }

        try {
            $writer->write($this->accountData->of($user), $path);
        } catch (\Throwable $exception) {
            unlink($path);

            throw $exception;
        }

        return new ExportedFileDto(
            $path,
            $writer->contentType(),
            \sprintf('my-gift-donnees-%s.%s', (new \DateTimeImmutable())->format('Y-m-d'), $writer->extension()),
        );
    }
}
