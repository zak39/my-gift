<?php

namespace App\Twig\Components\Molecule\Auth;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The login and the registration pages draw one card in two halves: the form (the content,
 * a card of its own) on the left, the other way in (SwitchPanel) on the right.
 */
#[AsTwigComponent]
final class SplitCard
{
    /** The page it sits on, one of SwitchPanel::PAGES. */
    public string $page;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing page would render an empty panel, with no error.
     */
    public function mount(string $page): void
    {
        if (!\in_array($page, SwitchPanel::PAGES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown auth page "%s".', $page));
        }

        $this->page = $page;
    }
}
