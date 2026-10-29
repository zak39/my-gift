<?php

namespace App\Enum;

/**
 * The look picked in the navbar, kept in a cookie (App\Services\ThemeCookie), so visitors
 * have one too. SYSTEM follows the device: the page then carries
 * no data-theme, and daisyUI's --default / --prefersdark themes (assets/styles/app.css) pick
 * emerald or dark from prefers-color-scheme.
 */
enum Theme: string
{
    case LIGHT = 'light';
    case DARK = 'dark';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::LIGHT => 'Clair',
            self::DARK => 'Sombre',
            self::SYSTEM => 'Système',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LIGHT => 'lucide:sun',
            self::DARK => 'lucide:moon',
            self::SYSTEM => 'lucide:monitor',
        };
    }

    /**
     * The daisyUI theme to set on <html>, or null to leave the choice to the device.
     */
    public function daisyTheme(): ?string
    {
        return match ($this) {
            self::LIGHT => 'emerald',
            self::DARK => 'dark',
            self::SYSTEM => null,
        };
    }
}
