<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Section
{
    public string $title;
    public ?string $description = null;
    public bool $checked = false;
    /** Anchor id, so the section index can link to this section and unfold it. */
    public ?string $anchor = null;
    public ?int $giftCount = null;
}
