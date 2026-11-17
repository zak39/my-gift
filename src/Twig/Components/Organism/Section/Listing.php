<?php

namespace App\Twig\Components\Organism\Section;

use App\Entity\Section;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Sections and their gifts, the first one unfolded unless the caller says otherwise: a user's
 * own list, a friend's, a crew's on its page and below each member's list
 * (Organism:Crew:SharedLists). The caller says what to show when there is none.
 */
#[AsTwigComponent]
final class Listing
{
    /** @var iterable<Section> */
    public iterable $sections = [];

    /**
     * The owners run the list: drafts show too, and an empty section says how to fill it.
     * Anyone else sees what is published.
     */
    public bool $ownerView = false;

    /**
     * False for a caller that picks the section to unfold itself, `$unfolded`: a friend's page
     * unfolds the first section they have something to see in, or else the block of the gifts
     * filed in none.
     */
    public bool $unfoldFirst = true;

    /** The section to unfold when `$unfoldFirst` is false: none when null. */
    public ?Section $unfolded = null;

    /** Level of each section's title, one below the heading the sections sit under. */
    public int $headingLevel = 2;
}
