<?php

namespace App\Twig\Components\Organism\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class CardActions
{
    public Gift $gift;
}
