<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class LinkButton
{
    public Gift $gift;
    public bool $iconOnly = false;
    public string $style = 'outline';
    public string $color = '';
}
