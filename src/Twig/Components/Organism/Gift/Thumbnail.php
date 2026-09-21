<?php

namespace App\Twig\Components\Organism\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Thumbnail
{
    public ?Gift $gift = null;

    /**
     * Sizing/fitting classes applied to the image, so each card layout can frame it its own way.
     * Inside a `card-side`, daisyUI forces `height: 100%` on the image, so a layout that sets a
     * height for the stacked breakpoint has to hand it back with `lg:h-full`.
     */
    public string $imageClass = 'h-full w-full object-cover';
}
