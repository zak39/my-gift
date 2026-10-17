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

    public function generate(string $firstname): UploadedFile
    {
        $content = $this->client->request(
            'GET',
            $this->getBaseApi().'/thumbs/webp',
            [
                'query' => [
                    'seed' => $this->seedFor($firstname),
                ],
            ]
        )->getContent();

        $tmpFile = tempnam(sys_get_temp_dir(), 'avatar_');

        file_put_contents($tmpFile, $content);

        return new UploadedFile(
            $tmpFile,
            'avatar.webp',
            'image/webp',
            null,
            true
        );
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
