<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * One question of the FAQ page, its answer as the content block: a native <details>, so it
 * opens without JavaScript and several can stay open side by side, unless they share a `name`
 * attribute (the landing page's).
 */
#[AsTwigComponent]
final class FaqItem
{
    public string $question;
}
