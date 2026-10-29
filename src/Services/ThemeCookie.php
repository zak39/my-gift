<?php

namespace App\Services;

use App\Enum\Theme;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFunction;

/**
 * The theme picked on this device, kept in a cookie rather than on the account: a visitor
 * picks one too, and a phone may stay dark while a desktop stays light. Written by the
 * browser (theme_controller.js), read here for base.html.twig to render <html data-theme>
 * before the first paint.
 */
final class ThemeCookie
{
    public const NAME = 'theme';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * SYSTEM without a cookie, or with a value the app does not know: the browser hands it,
     * anyone can write anything in it.
     */
    #[AsTwigFunction('current_theme')]
    public function current(): Theme
    {
        $value = $this->requestStack->getMainRequest()?->cookies->getString(self::NAME);

        return Theme::tryFrom($value ?? '') ?? Theme::SYSTEM;
    }
}
