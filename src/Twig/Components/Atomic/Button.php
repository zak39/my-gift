<?php

namespace App\Twig\Components\Atomic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Button
{
    public string $color = '';
    public string $style = 'outline';
    public string $size = 'md';
    public string $to = '#';
    public array $parameters = [];
    public bool $wide = false;
}
