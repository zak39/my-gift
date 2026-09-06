<?php

namespace App\Twig\Components\Organism;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Contributors
{
    public Gift $gift;
}
