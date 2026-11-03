<?php

namespace App\Twig\Components\Atomic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ButtonLink
{
    public string $color = '';
    public string $style = 'soft';
    public string $to;
    public bool $wide = false;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing `to` would render href="" in prod, with no error.
     */
    public function mount(string $to): void
    {
        $this->to = $to;
    }
}
