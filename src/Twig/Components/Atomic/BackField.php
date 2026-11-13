<?php

namespace App\Twig\Components\Atomic;

use App\Services\RefererRedirector;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The page a form was opened from, carried by the form so that its action goes back there
 * once done (RefererRedirector::origin()). Nothing without one: the action falls back.
 */
#[AsTwigComponent]
final class BackField
{
    public ?string $url = null;

    public function getName(): string
    {
        return RefererRedirector::BACK_FIELD;
    }
}
