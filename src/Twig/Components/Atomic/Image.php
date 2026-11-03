<?php

namespace App\Twig\Components\Atomic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Image
{
    public string $source;
    public string $alt;
}
