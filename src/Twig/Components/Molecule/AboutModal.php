<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * What the app is and how to reach its maintainer, opened from the avatar menu and the
 * mobile drawer; the full story is the « À propos » page. Rendered once, outside both
 * menus: a dialog inside a closed dropdown or drawer would be hidden with it, even opened.
 */
#[AsTwigComponent]
final class AboutModal
{
}
