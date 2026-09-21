<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:Button:Outline')]
final class Outline
{
    public string $color = 'primary';
    public string $to = '#';
    public string $size = 'md';
    public array $parameters = [];
    public bool $wide = false;
}
