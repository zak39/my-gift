<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Section
{
    public string $title;
    public ?string $description = null;
    public bool $checked = false;
    /** Anchor id, so the section index can link to this section and unfold it. */
    public ?string $anchor = null;
    public ?int $giftCount = null;

    /**
     * Level of the section's title: 2 under the page's title, deeper when the section sits
     * under a crew's heading (RGAA 9.1: the headings outline the page).
     */
    public int $headingLevel = 2;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing title would render an empty heading in prod, with no error.
     */
    public function mount(string $title): void
    {
        $this->title = $title;
    }
}
