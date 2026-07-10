<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('molecule:Avatar')]
final class Avatar
{
    public int $size = 10;
}
