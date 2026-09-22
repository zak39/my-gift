<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * One cell of a daisyUI `stats` band: icon, label, number, and a line of context.
 * Must stay a direct child of an element carrying the `stats` class.
 */
#[AsTwigComponent]
final class Stat
{
    /** A ux-icons name, such as `lucide:gift`. */
    public string $icon;
    public string $title;
    public string|int $value;
    public ?string $description = null;
    /** Paints the number with the brand colour, for the one figure that leads. */
    public bool $highlight = false;
}
