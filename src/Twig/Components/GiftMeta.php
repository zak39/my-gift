<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('molecule:GiftMeta')]
final class GiftMeta
{
    public string $label;
    public string $value;
    public string $badgeValueColor = 'accent';
    public string $badgeValueType = '';

    public function getColorBadgeValueClass(): string
    {
        return match ($this->badgeValueColor) {
            'accent' => 'badge-accent',
            'primary' => 'badge-primary',
            'secondary' => 'badge-secondary',
            'success' => 'badge-success',
            'error' => 'badge-error',
            default => 'badge-accent',
        };
    }
}
