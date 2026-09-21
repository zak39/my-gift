<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:ButtonLink')]
final class ButtonLink
{
    public string $color = '';
    public string $style = 'soft';
    public string $to;
    public bool $wide = false;
}
