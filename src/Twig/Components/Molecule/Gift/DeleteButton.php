<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class DeleteButton
{
    public Gift $gift;
    public bool $iconOnly = false;
    public string $color = '';
    public string $style = 'outline';

    /**
     * The list the button sits on, to go back to once the gift is deleted. None on the
     * gift's own page, which will be gone.
     */
    public ?string $back = null;
}
