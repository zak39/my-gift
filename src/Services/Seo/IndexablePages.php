<?php

namespace App\Services\Seo;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * The pages a search engine may index: the public ones, which say what the app is. Any other
 * is kept out (NoIndexListener), a page added later too, so that nobody has to think of it.
 * Only in prod: the preprod, a copy of it, must not compete with it in the results.
 */
final class IndexablePages
{
    // No route parameter: each one is a single page, at a single URL (its canonical one,
    // base.html.twig).
    private const ROUTES = [
        'app_landing_page',
        'app_faq',
        'app_about',
        'app_legal_notice',
        'app_privacy',
        'app_terms',
        'app_login',
        'app_register',
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function isIndexed(): bool
    {
        return 'prod' === $this->environment;
    }

    /**
     * The absolute URLs listed in the sitemap: none out of prod.
     *
     * @return list<string>
     */
    public function urls(): array
    {
        return $this->isIndexed()
            ? array_map(
                fn (string $route): string => $this->urlGenerator->generate($route, [], UrlGeneratorInterface::ABSOLUTE_URL),
                self::ROUTES,
            )
            : []
        ;
    }

    #[AsTwigFunction('is_indexable_route')]
    public function includes(?string $route): bool
    {
        return $this->isIndexed() && \in_array($route, self::ROUTES, true);
    }
}
