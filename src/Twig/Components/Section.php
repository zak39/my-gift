<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('molecule:Section')]
final class Section
{
    public string $title;
    public ?string $description = null;
    public bool $checked = false;
}
