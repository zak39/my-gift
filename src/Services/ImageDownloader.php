<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageDownloader
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function download(string $url): ?UploadedFile
    {
        try {
            $response = $this->httpClient
                ->request(
                    'GET',
                    $url,
                    [
                        'timeout' => 10,
                    ]
                )
            ;
        } catch (\Exception $error) {
            return null;
        }

        if ($response->getStatusCode() >= 400) {
            return null;
        }

        $contentType = $response->getHeaders()['content-type'][0] ?? '';

        if (!str_starts_with($contentType, 'image/')) {
            return null;
        }

        $extension = match (true) {
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'webp') => 'webp',
            default => 'jpeg',
        };

        $tmpPath = sys_get_temp_dir().'/og_image_'.bin2hex(random_bytes(8)).'.'.$extension;
        file_put_contents($tmpPath, $response->getContent());

        return new UploadedFile($tmpPath, 'og-image.'.$extension, $contentType, null, true);
    }
}
