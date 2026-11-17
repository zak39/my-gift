<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\Crew;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A crew and the way to its list, among the user's crews (/me/crews).
 */
#[AsTwigComponent]
final class Card
{
    public Crew $crew;
}
