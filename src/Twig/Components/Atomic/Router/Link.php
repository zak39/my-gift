<?php

namespace App\Twig\Components\Atomic\Router;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Link
{
    public string $name = '';
    public string $link;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing link would render <a href=""> in prod, with no error.
     */
    public function mount(string $link): void
    {
        $this->link = $link;
    }
}
