<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:Button:Soft')]
final class Soft
{
    public string $type = 'primary';
    public string $name;
}
