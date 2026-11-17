<?php

namespace App\Twig\Components\Atomic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The "Requis" badge beside a required field: dark on the light theme, light on the dark one.
 * Seen only: the field's required attribute already tells assistive technologies.
 */
#[AsTwigComponent]
final class RequiredBadge
{
}
