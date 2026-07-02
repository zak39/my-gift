<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Modal
{
    public int $id;
    public string $title;
    public string $class;
    public bool $full = false;
}
