<?php

namespace App\Services\Dto;

/**
 * The picture of a LinkPreviewDto card. Its type and size are given only when known, as for
 * the app's default picture: an uploaded one can be of any format.
 */
final class LinkPreviewImageDto
{
    /**
     * @param string $url absolute: a messenger fetches it from its own servers
     */
    public function __construct(
        public readonly string $url,
        public readonly string $alt,
        public readonly ?string $type = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
    ) {
    }
}
