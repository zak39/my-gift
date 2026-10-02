<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\CrewInvitation;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * An invitation waiting for the logged-in user's answer.
 */
#[AsTwigComponent]
final class ReceivedInvitation
{
    public CrewInvitation $invitation;
}
