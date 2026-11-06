<?php

namespace App\Twig\Components\Molecule\Auth;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The other way in, beside the login card and the registration card: on /login it invites a
 * newcomer to create an account, on /register it sends back to the login.
 */
#[AsTwigComponent]
final class SwitchPanel
{
    public const PAGES = ['login', 'register'];

    /** The page this panel sits on, one of PAGES. */
    public string $page;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing page would render an empty panel, with no error.
     */
    public function mount(string $page): void
    {
        if (!\in_array($page, self::PAGES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown auth page "%s".', $page));
        }

        $this->page = $page;
    }
}
