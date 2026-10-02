<?php

namespace App\Twig\Components\Organism\Crew;

use App\Entity\Crew;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Top of a crew's list, the crew's counterpart of Organism:GiftListHeader.
 */
#[AsTwigComponent]
final class Header
{
    public Crew $crew;

    /**
     * The members run the list: drafts count too, and the actions manage it.
     */
    public bool $ownerView = false;

    public function getGiftCount(): int
    {
        return $this->crew->getGiftCount($this->ownerView);
    }
}
