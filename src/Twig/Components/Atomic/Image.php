<?php

namespace App\Twig\Components\Atomic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Image
{
    public string $source;
    public string $alt;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing source or alt would render src="" or alt="" in prod, with no error.
     */
    public function mount(string $source, string $alt): void
    {
        $this->source = $source;
        $this->alt = $alt;
    }
}
