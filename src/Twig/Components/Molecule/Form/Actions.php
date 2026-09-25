<?php

namespace App\Twig\Components\Molecule\Form;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Footer of an Organism:Form:Card form: a way back, and the page's single primary action.
 * Must be rendered inside the <form>, since its button submits it.
 */
#[AsTwigComponent]
final class Actions
{
    public string $cancelUrl;
    public string $submitLabel;
    /** A ux-icons name, such as `lucide:check`. */
    public string $submitIcon = 'lucide:check';
}
