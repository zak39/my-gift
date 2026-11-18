<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Heading
{
    public Gift $gift;

    /**
     * Level of the gift's title: one below the heading of the section the card sits under
     * (RGAA 9.1: the headings outline the page).
     */
    public int $headingLevel = 2;
}
