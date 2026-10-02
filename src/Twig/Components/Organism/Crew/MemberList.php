<?php

namespace App\Twig\Components\Organism\Crew;

use App\Entity\Crew;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class MemberList
{
    public Crew $crew;
}
