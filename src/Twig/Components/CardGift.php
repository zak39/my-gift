<?php

namespace App\Twig\Components;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class CardGift
{
    public ?Gift $gift = null;

    /** Level of the gift's title: one below the heading of the section the card sits under. */
    public int $headingLevel = 2;
}
