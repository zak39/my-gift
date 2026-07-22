<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DicebearAvatarGenerator
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function getBaseApi(): string
    {
        return 'https://api.dicebear.com/10.x';
    }

    public function generate(string $seed): UploadedFile
    {
        $content = $this->client->request(
            'GET',
            $this->getBaseApi().'/thumbs/png',
            [
                'query' => [
                    'seed' => $seed,
                ],
            ]
        )->getContent();

        $tmpFile = tempnam(sys_get_temp_dir(), 'avatar_');

        file_put_contents($tmpFile, $content);

        return new UploadedFile(
            $tmpFile,
            'avatar.png',
            'image/png',
            null,
            true
        );
    }
}
