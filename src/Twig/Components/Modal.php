<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Modal
{
    /** Tells apart the modals of one class on a page; none for a modal found once. */
    public ?int $id = null;
    public string $title;
    public string $class;
    public bool $full = false;
}
