<?php

namespace App\Twig\Components\Organism;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The body of an error page (templates/bundles/TwigBundle/Exception/): its code, what
 * happened, and the way back home. Nothing here reads the user or the database: a routing
 * 404 comes before the firewall, and a 500 may come from the database itself.
 */
#[AsTwigComponent]
final class ErrorPage
{
    public int $statusCode;
    public string $title;
    public string $message;
}
