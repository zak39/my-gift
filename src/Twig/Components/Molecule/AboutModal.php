<?php

namespace App\Twig\Components\Molecule;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * What the app is and how to reach its maintainer, opened from the avatar menu and the
 * mobile drawer. Rendered once, outside both menus: a dialog inside a closed dropdown or
 * drawer would be hidden with it, even opened.
 */
#[AsTwigComponent]
final class AboutModal
{
    public function __construct(
        #[Autowire('%app.version%')]
        public readonly string $version,
        #[Autowire('%app.repository_url%')]
        public readonly string $repositoryUrl,
        #[Autowire('%app.contact_email%')]
        public readonly string $contactEmail,
        #[Autowire('%app.support_url%')]
        public readonly string $supportUrl,
    ) {
    }
}
