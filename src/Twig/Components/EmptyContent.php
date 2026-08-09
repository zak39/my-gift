<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('atomic:EmptyContent')]
final class EmptyContent
{
    public ?string $title = null;
    public ?string $description = null;
}
