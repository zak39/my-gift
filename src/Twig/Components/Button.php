<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:Button')]
final class Button
{
    public string $color = '';
    public string $style = 'outline';
    public string $size = 'md';
    public string $to = '#';
    public array $parameters = [];
    public bool $wide = false;
}
