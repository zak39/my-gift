<?php

namespace App\Services;

use App\Vich\UploadedImage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageDownloader
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UploadedImage $uploadedImage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * The image at $url as an upload, or null when it cannot be had: unreachable, longer than
     * $timeout seconds in all, an error status, or a body that is not an image following the
     * upload rules. Each reason is logged, at the info level: a page's image missing is no
     * incident; the caller warns when it is one (DicebearAvatarGenerator).
     *
     * @param string $name the client name, which gives the stored file its base name
     */
    public function download(string $url, string $name = 'og-image', float $timeout = 10): ?UploadedFile
    {
        try {
            $response = $this->httpClient
                ->request(
                    'GET',
                    $url,
                    [
                        'timeout' => $timeout,
                        'max_duration' => $timeout,
                    ]
                )
            ;

            // The request is lazy: a transport error or a timeout is thrown from here on
            $status = $response->getStatusCode();
            if ($status >= 400) {
                $this->logger->info('Image not downloaded: error status.', ['url' => $url, 'status' => $status]);

                return null;
            }

            $contentType = $response->getHeaders()['content-type'][0] ?? '';

            if (!str_starts_with($contentType, 'image/')) {
                $this->logger->info('Image not downloaded: not an image.', ['url' => $url, 'content_type' => $contentType]);

                return null;
            }

            $content = $response->getContent();
        } catch (\Exception $exception) {
            $this->logger->info('Image not downloaded: the request failed.', ['url' => $url, 'exception' => $exception]);

            return null;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'image_');
        file_put_contents($tmpPath, $content);

        // The Content-Type is the remote server's word: the content itself must be an image
        // following the upload rules, since no Assert\Image validates this file
        if (!$this->uploadedImage->accepts($tmpPath)) {
            unlink($tmpPath);
            $this->logger->info('Image not downloaded: it does not follow the upload rules.', ['url' => $url]);

            return null;
        }

        // Stored as WebP and named after its content: the client name only gives the base name
        return new UploadedFile($tmpPath, $name, mime_content_type($tmpPath), null, true);
    }
}
