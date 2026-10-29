<?php

namespace App\Twig\Components\Molecule;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The strip above the navbar telling visitors this is not the real app: shown in test and
 * preprod (APP_ENV=preprod, set by the server's .env.preprod.local). Rendered by base.html.twig
 * and the EasyAdmin layout (templates/bundles/EasyAdminBundle/layout.html.twig).
 */
#[AsTwigComponent]
final class TestBanner
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function isVisible(): bool
    {
        return \in_array($this->environment, ['test', 'preprod'], true);
    }
}
