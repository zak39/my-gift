<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('molecule:EmptyContentHero')]
final class EmptyContentHero
{
    public ?string $title = null;
    public ?string $description = null;
}
