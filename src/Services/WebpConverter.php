<?php

namespace App\Services;

use App\Vich\UploadedImage;

/**
 * Rewrites an image file as WebP in place, so that whoever moves it afterwards
 * (VichUploader) names it after its new content.
 */
class WebpConverter
{
    private const QUALITY = 80;

    public function __construct(
        private UploadedImage $uploadedImage,
    ) {
    }

    /**
     * @param int $maxSize the longest side, in pixels, the image may keep
     *
     * @throws \InvalidArgumentException when the file does not follow the UploadedImage rules
     * @throws \ImagickException         when the image cannot be decoded
     * @throws \RuntimeException         when the file cannot be rewritten
     */
    public function convert(string $path, int $maxSize): void
    {
        if (!$this->uploadedImage->accepts($path)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a %s image of at most %d pixels.', $path, UploadedImage::FORMATS, UploadedImage::MAX_PIXELS));
        }

        // The prefix ('jpeg:', 'png:', 'webp:') pins the decoder to the format checked above, and
        // '[0]' reads the first image only: an animated WebP is not decoded frame by frame
        $format = substr(mime_content_type($path), strlen('image/'));
        $image = new \Imagick($format.':'.$path.'[0]');

        $tooLarge = max($image->getImageWidth(), $image->getImageHeight()) > $maxSize;
        $hasMetadata = [] !== array_diff($image->getImageProfiles('*', false), ['icc']);

        // An animated WebP is rewritten even so, down to the first image read above
        if ('WEBP' === $image->getImageFormat() && !$tooLarge && !$hasMetadata && !$this->isAnimatedWebp($path)) {
            return;
        }

        // The EXIF orientation is applied to the pixels before the metadata goes away
        $image->autoOrient();

        $colorProfile = $image->getImageProfiles('icc')['icc'] ?? null;
        $image->stripImage();
        // WebP only stores RGB: a CMYK or grey image is converted, and its profile would no longer match
        $image->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
        if (null !== $colorProfile && 'RGB ' === substr($colorProfile, 16, 4)) {
            $image->profileImage('icc', $colorProfile);
        }

        if ($tooLarge) {
            $image->resizeImage($maxSize, $maxSize, \Imagick::FILTER_LANCZOS, 1, true);
        }

        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(self::QUALITY);

        // Not writeImage($path): ImageMagick would pick the format from the path's extension,
        // and PHP would keep the old file size in its stat cache
        $webp = $image->getImageBlob();
        if (strlen($webp) !== file_put_contents($path, $webp)) {
            throw new \RuntimeException(sprintf('Could not rewrite "%s" as WebP.', $path));
        }
    }

    /**
     * Read from the header rather than through Imagick, which only loaded the first image: an
     * extended WebP ('VP8X' chunk, from byte 12) raises the animation bit of its flags (byte 20).
     */
    private function isAnimatedWebp(string $path): bool
    {
        $header = (string) file_get_contents($path, false, null, 0, 21);

        return 21 === strlen($header)
            && 'VP8X' === substr($header, 12, 4)
            && 0 !== (ord($header[20]) & 0x02)
        ;
    }
}
