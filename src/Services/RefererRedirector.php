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
    /** The field a form carries the page it was opened from in (Atomic:BackField). */
    public const BACK_FIELD = '_back';

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
        return $this->local($request, $request->headers->get('referer'));
    }

    /**
     * The page an action was started from, to go back there once it is done: the referer
     * while its form is shown, then the form's own BACK_FIELD once it is sent, since the
     * referer of a submission is the form's page. A crew's section or gift is run from
     * the crew's page as from each member's list (/me/sections, /users/{id}/sections).
     *
     * Never the login page: reached after a session ran out, the form has it as its referer,
     * and going back there would ask to log in again.
     */
    public function origin(Request $request): ?string
    {
        $url = $request->isMethod('GET')
            ? $this->localReferer($request)
            : $this->local($request, $request->request->getString(self::BACK_FIELD))
        ;

        if (null !== $url && parse_url($url, \PHP_URL_PATH) === $this->urlGenerator->generate('app_login')) {
            return null;
        }

        return $url;
    }

    /** The URL if it is one of this site's: it comes from the client, so a foreign one is dropped. */
    private function local(Request $request, ?string $url): ?string
    {
        return null !== $url && str_starts_with($url, $request->getSchemeAndHttpHost().'/') ? $url : null;
    }
}
