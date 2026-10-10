<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The success and error flashes of the request, one Alert each: a page an action may
 * redirect to shows them wherever it renders this component.
 */
#[AsTwigComponent]
final class Flashes
{
    /** Spacing of each Alert, depending on where the page places them. */
    public string $alertClass = 'mb-4';
}
