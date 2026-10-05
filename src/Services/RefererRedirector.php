<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Back to the page a button was on, for actions offered on several pages (the friend
 * buttons sit on the search, which keeps its query and page in the URL).
 */
final class RefererRedirector
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * The referer comes from the client, hence the same-site check before following it.
     */
    public function back(Request $request, string $fallbackRoute): RedirectResponse
    {
        $referer = $request->headers->get('referer');

        if (null !== $referer && str_starts_with($referer, $request->getSchemeAndHttpHost().'/')) {
            return new RedirectResponse($referer);
        }

        return new RedirectResponse($this->urlGenerator->generate($fallbackRoute));
    }
}
