<?php

namespace App\Services\Dto;

/**
 * The card a messenger (Telegram, WhatsApp, Slack…) unfolds a link into, published as Open
 * Graph tags by Molecule:LinkPreview: a shared link's, or the app's own for any other page.
 * Built by LinkPreviewBuilder; not to be confused with ProductPageDto, what the app reads from
 * a product page.
 */
final class LinkPreviewDto
{
    /**
     * @param string $url absolute, the link itself: the card a messenger caches is the one of og:url
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $url,
        public readonly LinkPreviewImageDto $image,
    ) {
    }
}
