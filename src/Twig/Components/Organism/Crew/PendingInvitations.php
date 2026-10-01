<?php

namespace App\Twig\Components\Organism\Crew;

use App\Entity\CrewInvitation;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The invitations waiting for the logged-in user's answer. Renders nothing without any.
 */
#[AsTwigComponent]
final class PendingInvitations
{
    /** @var list<CrewInvitation> */
    public array $invitations = [];
}
