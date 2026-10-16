<?php

namespace App\EventListener;

use App\Services\LinkPreviewBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * A link copied with "Partager" (ShareUrlGenerator), opened by a visitor who is not logged
 * in: a link-preview bot (Telegram, WhatsApp, Slack…), which never logs in, or a friend
 * about to. Instead of the redirect to /login, they get the login page right at the link's
 * URL, carrying the card of what it shows: a bot following the redirect would unfold the
 * login page's card. Logging in there leads to the link: the form carries it as its
 * `_target_path`, rather than the session, which a bot would open on every fetch.
 *
 * Not the firewall's entry point: denied access forces a 403 on any response of it but a
 * redirect, and a messenger shows no card for a 403.
 */
final class SharedLinkPreviewListener
{
    public function __construct(
        private readonly LinkPreviewBuilder $linkPreviewBuilder,
        private readonly Security $security,
        private readonly Environment $twig,
    ) {
    }

    // After the firewall (priority 8), once the user is known. Only a shared page carrying a
    // signature goes further: the others do not get the lazy firewall to load a user.
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // HEAD too: some bots probe a link before fetching it.
        if (
            !$event->isMainRequest()
            || !\in_array($request->getMethod(), ['GET', 'HEAD'], true)
            || !$request->query->has('_hash')
            || !$this->linkPreviewBuilder->isSharedPage($request)
        ) {
            return;
        }

        // A friend logged in opens the page itself, at the plain URL they would copy from
        // the address bar: the signature only ever unfolds a card.
        if (null !== $this->security->getUser()) {
            $event->setResponse(new RedirectResponse($this->withoutSignature($request)));

            return;
        }

        $linkPreview = $this->linkPreviewBuilder->forSharedLink($request);

        if (null === $linkPreview) {
            return;
        }

        // The card is for messengers, not search engines: a signed link posted in public
        // must not get the gift's details indexed.
        $event->setResponse(new Response($this->twig->render('security/login.html.twig', [
            'last_username' => '',
            'error' => null,
            'linkPreview' => $linkPreview,
            'target_path' => $request->getRequestUri(),
        ]), headers: ['X-Robots-Tag' => 'noindex']));
    }

    /**
     * The link as ShareUrlGenerator signed it, less what UriSigner added to it.
     */
    private function withoutSignature(Request $request): string
    {
        $query = $request->query->all();
        unset($query['_hash'], $query['_expiration']);

        return $request->getUriForPath($request->getPathInfo()).([] === $query ? '' : '?'.http_build_query($query));
    }
}
