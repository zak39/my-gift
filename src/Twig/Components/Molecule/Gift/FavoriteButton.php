<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class FavoriteButton
{
    public ?Gift $gift = null;
    public bool $isFavorite = false;
}
