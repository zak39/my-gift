<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The "…" menu: an icon-only trigger, named by its label for screen readers, opening the
 * entries the caller puts in its content block (one <li> each). The dropdown controller
 * keeps the trigger's aria-expanded in step and brings the focus back to it when a dialog
 * opened from an entry closes.
 */
#[AsTwigComponent]
final class MoreActions
{
    public string $label;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing label would leave the trigger unnamed in prod, with no error.
     */
    public function mount(string $label): void
    {
        $this->label = $label;
    }
}
