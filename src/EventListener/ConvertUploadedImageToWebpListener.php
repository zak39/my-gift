<?php

namespace App\EventListener;

use App\Services\WebpConverter;
use App\Vich\UploadedImage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;

/**
 * Every uploaded image is stored as WebP: the file is rewritten before Vich names and
 * moves it, so SmartUniqueNamer guesses the .webp extension from its new content.
 */
final class ConvertUploadedImageToWebpListener
{
    public function __construct(
        private WebpConverter $webpConverter,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: Events::PRE_UPLOAD)]
    public function onPreUpload(Event $event): void
    {
        $mapping = $event->getMapping();
        $mappingName = $mapping->getMappingName();
        $file = $mapping->getFile($event->getObject());

        // Only a temporary upload may be rewritten in place. A ReplacingFile (set by hand, e.g. in
        // fixtures) is an existing file Vich copies: converting it would alter that original
        if (!$file instanceof UploadedFile) {
            return;
        }

        $maxSize = UploadedImage::MAX_SIZES[$mappingName]
            ?? throw new \LogicException(sprintf('No WebP max size for the Vich mapping "%s".', $mappingName))
        ;

        try {
            $this->webpConverter->convert($file->getPathname(), $maxSize);
        } catch (\ImagickException $exception) {
            // A JPEG, PNG or WebP by its content that Imagick cannot decode: it is kept as uploaded,
            // the file being untouched. Not the converter's other exceptions, after which keeping it
            // would store a file that is truncated (\RuntimeException) or breaks the upload rules
            // (\InvalidArgumentException): the upload had better fail
            $this->logger->warning('Uploaded image kept as is: it could not be converted to WebP.', [
                'mapping' => $mappingName,
                'exception' => $exception,
            ]);
        }
    }
}
