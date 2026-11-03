<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A plain link; drawn as a button, it is an Atomic:ButtonLink. With newWindow, it opens in a
 * new tab and tells screen readers so (RGAA 13.2), as Atomic:ButtonLink's newWindow does.
 */
#[AsTwigComponent]
final class Link
{
    public string $href;
    public bool $newWindow = false;
}
