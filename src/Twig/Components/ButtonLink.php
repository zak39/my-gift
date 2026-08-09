<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:ButtonLink')]
final class ButtonLink
{
    public string $type = "primary";
    public string $name;
    public string $to;
}
