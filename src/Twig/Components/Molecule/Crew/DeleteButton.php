<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\Crew;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Deleting the crew, the creator's only way out, once confirmed in a modal that says
 * where the gifts go.
 */
#[AsTwigComponent]
final class DeleteButton
{
    public Crew $crew;
}
