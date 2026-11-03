<?php

namespace App\Twig\Components\Atomic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Button
{
    public string $color = '';
    public string $style = 'outline';
    public bool $wide = false;
}
