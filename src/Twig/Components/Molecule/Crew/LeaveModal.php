<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\Crew;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Leaving the crew, for any member but its owner, once confirmed in a modal that says
 * what becomes of the gifts they added. The modal alone, id `modal_crew_leave_{crew id}`:
 * whatever opens it goes through the dialog controller, a "…" menu entry included.
 */
#[AsTwigComponent]
final class LeaveModal
{
    public Crew $crew;
}
