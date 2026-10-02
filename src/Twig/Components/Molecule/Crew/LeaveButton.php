<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\Crew;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Leaving the crew, for any member but its creator, once confirmed in a modal that says
 * what becomes of the gifts they added.
 */
#[AsTwigComponent]
final class LeaveButton
{
    public Crew $crew;
}
