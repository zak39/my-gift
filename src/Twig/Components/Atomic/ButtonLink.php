<?php

namespace App\Twig\Components\Atomic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ButtonLink
{
    public string $color = '';
    public string $style = 'soft';
    public string $to;
    public bool $wide = false;
}
