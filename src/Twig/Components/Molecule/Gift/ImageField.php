<?php

namespace App\Twig\Components\Molecule\Gift;

use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The gift image upload with a square preview, driven by file_preview_controller.js.
 * The fallback is what the preview shows while no file is picked: the OpenGraph image
 * on creation, the current image on edit. It is rendered server-side too, so the preview
 * is right before Stimulus connects and after every Live Component re-render.
 */
#[AsTwigComponent]
final class ImageField
{
    public FormView $field;
    public string $fallbackUrl = '';
    public string $fallbackCaption = 'Image trouvée automatiquement.';
    public ?string $help = null;
    /** Extra attributes for the file input, such as a Live Component `data-loading`. */
    public array $widgetAttr = [];
}
