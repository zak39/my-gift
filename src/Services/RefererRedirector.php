<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Back to the page a button was on, for actions offered on several pages (the friend
 * buttons sit on the search, which keeps its query and page in the URL), and the URL of
 * a page's « Retour » link.
 */
final class RefererRedirector
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function back(Request $request, string $fallbackRoute): RedirectResponse
    {
        return new RedirectResponse($this->localReferer($request) ?? $this->urlGenerator->generate($fallbackRoute));
    }

    /**
     * The page the user came from, if it is one of this site's: the referer comes from the
     * client, so an external or forged one is dropped (null) rather than linked to.
     */
    public function localReferer(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        return null !== $referer && str_starts_with($referer, $request->getSchemeAndHttpHost().'/') ? $referer : null;
    }
}
