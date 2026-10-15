<?php

namespace App\Services;

use App\Vich\UploadedImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageDownloader
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UploadedImage $uploadedImage,
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

        $tmpPath = tempnam(sys_get_temp_dir(), 'og_image_');
        file_put_contents($tmpPath, $response->getContent());

        // The Content-Type is the remote server's word: the content itself must be an image
        // following the upload rules, since no Assert\Image validates this file
        if (!$this->uploadedImage->accepts($tmpPath)) {
            unlink($tmpPath);

            return null;
        }

        // Stored as WebP and named after its content: the client name only gives the base name
        return new UploadedFile($tmpPath, 'og-image', mime_content_type($tmpPath), null, true);
    }
}
