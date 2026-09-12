<?php

namespace App\Twig\Components\Organism\Dashboard;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Stats
{
    public int $myGiftsCount;
    public int $myParticipatingGiftsCount;
    public int $myReservedGiftsCount;
}
