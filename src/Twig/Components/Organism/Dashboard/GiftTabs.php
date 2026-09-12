<?php

namespace App\Twig\Components\Organism\Dashboard;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class GiftTabs
{
    /** @var \App\Entity\Gift[] */
    public array $myGifts = [];

    /** @var \App\Entity\Gift[] */
    public array $myFavoritesGifts = [];

    /** @var \App\Entity\Gift[] */
    public array $myContributingGifts = [];

    /** @var \App\Entity\Gift[] */
    public array $myReservedGifts = [];

    /** @var \App\Entity\Gift[] */
    public array $myLastParticipationGifts = [];

    /** @var \App\Entity\Gift[] */
    public array $myLastReservedParticipationGifts = [];

    /** @var \App\Entity\Gift[] */
    public array $myLastParticipatingParticipationGifts = [];
}
