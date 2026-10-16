<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Downloads a default avatar from the public DiceBear API, which promises no availability.
 */
class DicebearAvatarGenerator
{
    /** Seconds the whole request may last: someone waits for it, at registration. */
    private const TIMEOUT = 5;

    public function __construct(
        private ImageDownloader $imageDownloader,
        private LoggerInterface $logger,
    ) {
    }

    public function getBaseApi(): string
    {
        return 'https://api.dicebear.com/10.x';
    }

    /**
     * A WebP "thumbs" avatar, or null whatever goes wrong (ImageDownloader::download()): the
     * user is then left without one.
     */
    public function generate(string $firstname): ?UploadedFile
    {
        $url = $this->getBaseApi().'/thumbs/webp?'.http_build_query(['seed' => $this->seedFor($firstname)]);
        $avatar = $this->imageDownloader->download($url, 'avatar', self::TIMEOUT);

        if (null === $avatar) {
            $this->logger->warning('No default avatar: DiceBear did not provide one.');
        }

        return $avatar;
    }

    /**
     * The random part gives two users with the same first name different avatars,
     * and the hash keeps the first name itself from being sent to Dicebear.
     */
    private function seedFor(string $firstname): string
    {
        return hash('sha256', $firstname.bin2hex(random_bytes(16)));
    }
}
