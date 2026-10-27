<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Taking over a crew gift whose author deleted their account (gift.can_claim, ClaimGift),
 * once confirmed in a modal that says why the button is there and what taking it over changes.
 */
#[AsTwigComponent]
final class ClaimButton
{
    public Gift $gift;
}
