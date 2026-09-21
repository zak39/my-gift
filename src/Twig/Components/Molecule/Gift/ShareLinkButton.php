<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ShareLinkButton
{
    public Gift $gift;
    public bool $hideLabelOnMobile = false;
    public string $color = '';
    public string $style = 'outline';
}
