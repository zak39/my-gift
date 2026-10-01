<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\Crew;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A crew and the way to its list, among the user's crews or on a member's page.
 */
#[AsTwigComponent]
final class Card
{
    public Crew $crew;
}
