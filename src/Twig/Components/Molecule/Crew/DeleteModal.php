<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\Crew;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Deleting the crew, for its owner only, once confirmed in a modal that says
 * where the gifts go. The modal alone, id `modal_crew_delete_{crew id}`: whatever
 * opens it goes through the dialog controller, a "…" menu entry included.
 */
#[AsTwigComponent]
final class DeleteModal
{
    public Crew $crew;
}
