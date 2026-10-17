<?php

namespace App\Services;

use App\Vich\UploadedImage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ImageDownloader
{
    /**
     * The most bytes a body may weigh: it is written to disk, never held in memory, but a
     * product page's image has no reason to weigh more.
     */
    private const MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(
        #[Autowire(service: 'app.public_http_client')]
        private HttpClientInterface $httpClient,
        private UploadedImage $uploadedImage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * The image at $url as an upload, or null when it cannot be had: unreachable, longer than
     * $timeout seconds in all, an error status, a body over MAX_BYTES or that is not an image
     * following the upload rules, or no temporary file to write it to. Each reason is logged,
     * at the info level: a page's image missing is no incident; the caller warns when it is
     * one (DicebearAvatarGenerator).
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
                        // Or the client keeps its own copy of the body in php://temp, on top of
                        // the temporary file the chunks are written to
                        'buffer' => false,
                    ]
                )
            ;

            // The request is lazy: a transport error or a timeout is thrown from here on
            $status = $response->getStatusCode();
            if ($status >= 400) {
                $this->logger->info('Image not downloaded: error status.', ['url' => $url, 'status' => $status]);

                return null;
            }

            $headers = $response->getHeaders();
            $contentType = $headers['content-type'][0] ?? '';

            if (!str_starts_with($contentType, 'image/')) {
                $this->logger->info('Image not downloaded: not an image.', ['url' => $url, 'content_type' => $contentType]);

                return null;
            }

            // Refused before a byte of the body comes, when the server says so; it may say
            // nothing, or lie: the body is counted as it comes as well
            $length = (int) ($headers['content-length'][0] ?? 0);
            if ($length > self::MAX_BYTES) {
                $response->cancel();
                $this->logger->info('Image not downloaded: too large.', ['url' => $url, 'bytes' => $length]);

                return null;
            }

            $tmpPath = $this->writeToTemporaryFile($response, $url);
        } catch (\Exception $exception) {
            $this->logger->info('Image not downloaded: the request failed.', ['url' => $url, 'exception' => $exception]);

            return null;
        }

        if (null === $tmpPath) {
            return null;
        }

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

    /**
     * The path of a temporary file holding the body, written chunk by chunk as it comes, or
     * null (logged) when no file can be written or the body grows past MAX_BYTES. The file is
     * removed whenever null is returned or an exception is thrown.
     */
    private function writeToTemporaryFile(ResponseInterface $response, string $url): ?string
    {
        // A full or read-only temporary directory: both warn and return false
        $tmpPath = @tempnam(sys_get_temp_dir(), 'image_');
        $handle = false === $tmpPath ? false : @fopen($tmpPath, 'w');

        if (false === $handle) {
            $response->cancel();
            if (false !== $tmpPath) {
                @unlink($tmpPath);
            }
            $this->logger->info('Image not downloaded: no temporary file could be written.', ['url' => $url]);

            return null;
        }

        $received = 0;
        $kept = false;

        try {
            foreach ($this->httpClient->stream($response) as $chunk) {
                $content = $chunk->getContent();

                $length = strlen($content);
                $received += $length;

                if ($received > self::MAX_BYTES) {
                    $response->cancel();
                    $this->logger->info('Image not downloaded: too large.', ['url' => $url, 'bytes' => $received]);

                    return null;
                }

                // False on failure, fewer bytes when the disk fills up mid-chunk: an image cut
                // short may still pass accepts(), which reads its header only
                if (fwrite($handle, $content) !== $length) {
                    $response->cancel();
                    $this->logger->info('Image not downloaded: no temporary file could be written.', ['url' => $url]);

                    return null;
                }
            }

            $kept = true;
        } finally {
            fclose($handle);

            if (!$kept) {
                unlink($tmpPath);
            }
        }

        return $tmpPath;
    }
}
