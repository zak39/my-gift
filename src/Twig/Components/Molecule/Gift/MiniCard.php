<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class MiniCard
{
    public ?Gift $gift = null;

    /** Level of the gift's title: one below the heading it sits under. */
    public int $headingLevel = 2;
}
