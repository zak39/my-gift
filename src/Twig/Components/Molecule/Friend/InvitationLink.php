<?php

namespace App\Twig\Components\Molecule\Friend;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The user's invitation link, first thing on « Mes ami·es »: to copy and send, to
 * customize, or to draw again once confirmed in a modal, when it leaked.
 */
#[AsTwigComponent]
final class InvitationLink
{
    /** The user's own slug (FriendBook::$invitationSlug). */
    public string $slug;

    /**
     * Required through mount(): a typed property left unset is skipped on its way to the
     * template, so a missing slug would only fail in url(), with a 500.
     */
    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }
}
