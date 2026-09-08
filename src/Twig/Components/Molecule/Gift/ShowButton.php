<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ShowButton
{
    public Gift $gift;
    public bool $hideLabelOnMobile = false;
}
