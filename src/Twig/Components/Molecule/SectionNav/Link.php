<?php

namespace App\Twig\Components\Molecule\SectionNav;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * One entry of Molecule:SectionNav: a link to a block of the page, wired to its section-nav
 * controller, and the block's gift count, left out for a block that holds no gifts (a crew's
 * members). What the tag wraps (a crew's sections) is nested under the entry.
 */
#[AsTwigComponent]
final class Link
{
    /** Id of the block the link leads to. */
    public string $anchor;

    public string $icon;

    public string $label;

    public ?int $count = null;

    /**
     * Required through mount(), but the count: a typed property left unset is skipped on
     * its way to the template, so a missing one would render a broken entry in prod, with
     * no error.
     */
    public function mount(string $anchor, string $icon, string $label, ?int $count = null): void
    {
        $this->anchor = $anchor;
        $this->icon = $icon;
        $this->label = $label;
        $this->count = $count;
    }
}
