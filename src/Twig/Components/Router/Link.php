<?php

namespace App\Twig\Components\Router;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:Router:Link')]
final class Link
{
    public string $name = '';
    public ?string $link = null;
}
