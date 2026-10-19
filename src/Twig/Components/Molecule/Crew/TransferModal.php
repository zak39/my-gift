<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\Crew;
use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The owner passing the crew on to another member, once confirmed in a modal that says
 * what changes for each of them. Opened from the member's « … » menu (MemberRow): a closed
 * dropdown hides its content with `display: none`, so the modal cannot live inside it.
 */
#[AsTwigComponent]
final class TransferModal
{
    public Crew $crew;

    public User $member;
}
