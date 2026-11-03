<?php

namespace App\Twig\Components\Atomic\Router;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Link
{
    public string $name = '';
    public ?string $link = null;
}
