<?php

namespace App\Vich;

/**
 * What an image must be to be uploaded. The entities' Assert\Image constraints read the
 * constants; ImageDownloader and WebpConverter check a file with accepts().
 */
final readonly class UploadedImage
{
    /**
     * Read from the file's content, not its name.
     */
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * MIME_TYPES as users read them, in validation messages: change both together.
     */
    public const FORMATS = 'JPEG, PNG, WebP';

    /**
     * Decoding takes about 6 bytes a pixel, outside PHP's memory_limit: 50 million
     * pixels already need some 300 MB.
     */
    public const MAX_PIXELS = 50_000_000;

    /**
     * The longest side an image keeps once stored, in pixels, per Vich mapping
     * (config/packages/vich_uploader.yaml).
     */
    public const MAX_SIZES = [
        'imageGift' => 1200,
        'avatar' => 256,
    ];

    /**
     * Whether the file follows these rules, read from its content and header only:
     * nothing is decoded.
     */
    public function accepts(string $path): bool
    {
        return $this->hasAcceptedMimeType($path) && $this->isWithinPixelLimit($path);
    }

    /**
     * The type is read from the content (libmagic), whatever the file is named.
     */
    private function hasAcceptedMimeType(string $path): bool
    {
        return \in_array(mime_content_type($path), self::MIME_TYPES, true);
    }

    /**
     * The dimensions are read from the header only: a 27 KB file can announce 81 million pixels.
     */
    private function isWithinPixelLimit(string $path): bool
    {
        $size = @getimagesize($path);
        if (false === $size) {
            return false;
        }

        return $size[0] * $size[1] <= self::MAX_PIXELS;
    }
}
