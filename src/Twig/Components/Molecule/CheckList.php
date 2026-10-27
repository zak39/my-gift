<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A list of short promises, each behind a check mark. The caller sets the spacing around
 * it through its attributes.
 */
#[AsTwigComponent]
final class CheckList
{
    /** @var list<string> */
    public array $items = [];

    /**
     * The check's full colour classes, such as 'bg-success/15 text-success': Tailwind only
     * generates classes it finds written out, so the caller writes them in its template.
     */
    public string $iconClass;
}
