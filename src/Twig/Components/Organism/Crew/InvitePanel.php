<?php

namespace App\Twig\Components\Organism\Crew;

use App\Entity\Crew;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Inviting friends into the crew, and the invitations still waiting for an answer.
 */
#[AsTwigComponent]
final class InvitePanel
{
    public Crew $crew;

    /** A CrewInvitationType, whose choices are the friends the user may still invite. */
    public FormView $form;
}
