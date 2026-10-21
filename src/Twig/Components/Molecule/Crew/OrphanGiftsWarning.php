<?php

namespace App\Twig\Components\Molecule\Crew;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * What a crew section's or a crew's deletion does to the gifts whose author deleted their
 * account (ReleaseGifts), in both confirm dialogs.
 */
#[AsTwigComponent]
final class OrphanGiftsWarning
{
}
