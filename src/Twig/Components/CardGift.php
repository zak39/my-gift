<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class CardGift
{
    public int $id;
    public string $title;
    public ?string $description = null;
    public float $price;
    public ?string $link = null;
}
