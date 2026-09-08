<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('molecule:GiftContributingProgress')]
final class GiftContributingProgress
{
    public int $percent = 0;
    public string $color = 'accent';

    public function getColorClass(): string
    {
        return match ($this->color) {
            'accent' => 'text-accent',
            'primary' => 'text-primary',
            'secondary' => 'text-secondary',
            'error' => 'text-error',
            default => 'text-accent',
        };
    }
}
