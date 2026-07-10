<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:Image')]
final class Image
{
    public string $source;
    public string $alt;
}
