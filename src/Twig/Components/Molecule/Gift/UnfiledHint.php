<?php

namespace App\Twig\Components\Molecule\Gift;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Tells the owner of gifts filed in no section that sections sort them. For the owner
 * alone: the caller checks it, their friends see those gifts without the hint.
 */
#[AsTwigComponent]
final class UnfiledHint
{
}
