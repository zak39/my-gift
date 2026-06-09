<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:Button:Outline')]
final class Outline
{
    public string $type = 'primary';
    public string $name;
    public string $size = 'md';
}
