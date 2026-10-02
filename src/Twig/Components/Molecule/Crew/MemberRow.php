<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A member in the crew's list of members. No action on it: nobody takes anyone else out
 * of a crew.
 */
#[AsTwigComponent]
final class MemberRow
{
    public User $member;

    /** The member who created the crew, the only one who can delete it. */
    public bool $creator = false;
}
