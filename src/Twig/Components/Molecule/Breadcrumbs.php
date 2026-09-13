<?php

namespace App\Twig\Components\Molecule;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Breadcrumbs
{
    /** @var array<int, array{label: string, url?: string|null}> */
    public array $items = [];
}
