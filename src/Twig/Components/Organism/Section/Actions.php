<?php

namespace App\Twig\Components\Organism\Section;

use App\Entity\Section;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Actions
{
    public Section $section;
}
