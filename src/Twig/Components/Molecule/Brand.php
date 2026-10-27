<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The app's mark, its gift and its name « My Gift », written once: the header, the footer,
 * the « À propos » page and modal. Only the mark: whoever needs a link wraps it in one.
 */
#[AsTwigComponent]
final class Brand
{
    /** md in the header and the footer, lg as a title (the « À propos » page and modal). */
    public string $size = 'md';
}
