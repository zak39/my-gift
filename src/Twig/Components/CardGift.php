<?php

namespace App\Twig\Components;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class CardGift
{
    public ?Gift $gift = null;
}
