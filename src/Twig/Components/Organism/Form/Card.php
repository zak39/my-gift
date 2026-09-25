<?php

namespace App\Twig\Components\Organism\Form;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Shell of the create / edit pages: the gift-wrap ribbon, then a header saying what the
 * page does. The form goes in the `content` block, extra header details (badges, counts)
 * in the `meta` block.
 */
#[AsTwigComponent]
final class Card
{
    /** A ux-icons name, such as `lucide:gift`. */
    public string $icon;
    public string $title;
    public ?string $description = null;
}
