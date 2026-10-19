<?php

namespace App\Twig\Components\Molecule\Crew;

use App\Entity\CrewMember;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A member in the crew's list of members. Its owner may make another member owner in
 * their place; nobody takes anyone else out of a crew.
 */
#[AsTwigComponent]
final class MemberRow
{
    public CrewMember $membership;
}
