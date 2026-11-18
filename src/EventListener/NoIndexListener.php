<?php

namespace App\EventListener;

use App\Services\Seo\IndexablePages;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Keeps out of search engines every page but the public ones (IndexablePages): an invitation
 * link or a shared link too, posted in public. A header rather than a <meta> tag, so that a
 * redirect or a download (the data export) is covered too; not the uploaded images, which
 * the web server sends without the app (robots.txt). robots.txt cannot do it for a page: a
 * search engine kept from it never reads its noindex, and may still list its bare URL when
 * another site links to it.
 */
final class NoIndexListener
{
    public function __construct(
        private readonly IndexablePages $indexablePages,
    ) {
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        if (
            !$event->isMainRequest()
            || $this->indexablePages->includes($event->getRequest()->attributes->get('_route'))
        ) {
            return;
        }

        $event->getResponse()->headers->set('X-Robots-Tag', 'noindex');
    }
}
