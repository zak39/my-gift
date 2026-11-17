<?php

namespace App\Twig\Components\Molecule;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\Section;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SectionNav
{
    /** @var iterable<Section> */
    public iterable $sections = [];

    /** @var iterable<Gift> */
    public iterable $giftsWithoutSection = [];

    /** Counts unpublished gifts too, so the index matches what the page renders. */
    public bool $includeDrafts = false;

    /**
     * The crews of the list's owner, listed after the sections so they show without
     * scrolling: anchors to their blocks in Organism:Crew:SharedLists, then to their
     * sections there.
     *
     * @var iterable<Crew>
     */
    public iterable $crews = [];

    /**
     * @param iterable<Gift> $gifts
     */
    public function giftCount(iterable $gifts): int
    {
        $count = 0;

        foreach ($gifts as $gift) {
            if ($this->includeDrafts || $gift->isPublic()) {
                ++$count;
            }
        }

        return $count;
    }

    public function hasGiftsWithoutSection(): bool
    {
        return $this->giftCount($this->giftsWithoutSection) > 0;
    }
}
