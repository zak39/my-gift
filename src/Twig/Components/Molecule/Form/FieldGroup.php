<?php

namespace App\Twig\Components\Molecule\Form;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * One group of fields inside an Organism:Form:Card: its title and purpose sit in a left
 * column from `md` up, and stack above the fields on mobile.
 */
#[AsTwigComponent]
final class FieldGroup
{
    public string $title;
    public ?string $description = null;
}
