<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\CrewInvitation;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * An invitation of the crew still waiting for an answer, as its members see it.
 */
#[AsTwigComponent]
final class SentInvitation
{
    public CrewInvitation $invitation;
}
