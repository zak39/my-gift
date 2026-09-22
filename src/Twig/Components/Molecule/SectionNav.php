<?php

namespace App\Twig\Components\Molecule;

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
