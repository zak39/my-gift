<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class EmptyContentHero
{
    public ?string $title = null;
    public ?string $description = null;
}
