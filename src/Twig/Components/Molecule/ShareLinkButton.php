<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A "Partager" button: copies a link from ShareUrlGenerator, which the caller picks for
 * what it shares (gift_share_url(), user_list_share_url(), crew_share_url()). Shrunk to an
 * icon, as in a gift card, its label becomes a tooltip and the screen readers' text.
 */
#[AsTwigComponent]
final class ShareLinkButton
{
    public string $url;
    public string $label = 'Partager';
    public bool $iconOnly = false;
    public string $style = 'outline';
    public string $color = '';
}
