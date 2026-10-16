<?php

namespace App\Twig\Components\Molecule;

use App\Services\Dto\LinkPreviewDto;
use App\Services\LinkPreviewBuilder;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The Open Graph tags a messenger reads to unfold a link into a card, and the Twitter Card
 * type for the clients that want one (they read the og:* tags for the rest). Without a
 * preview, the app's own card: the template has no case to tell apart.
 */
#[AsTwigComponent]
final class LinkPreview
{
    public LinkPreviewDto $preview;

    public function __construct(
        private readonly LinkPreviewBuilder $linkPreviewBuilder,
    ) {
    }

    public function mount(?LinkPreviewDto $preview = null): void
    {
        $this->preview = $preview ?? $this->linkPreviewBuilder->forApp();
    }
}
