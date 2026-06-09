<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:Button')]
final class Button
{
    public string $type = 'primary';
    public string $name = '';
    public string $size = 'md';
    public string $to = '#';
}
