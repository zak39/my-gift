<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class CardGift
{
    public string $title;
    public ?string $description = null;
    public float $price;
    public ?string $link = null;
}
