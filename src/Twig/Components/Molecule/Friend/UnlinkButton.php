<?php

namespace App\Twig\Components\Molecule\Friend;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Cuts the link with a person, once confirmed in a modal: removing a friend or blocking
 * someone takes the pair out of each other's lists.
 */
#[AsTwigComponent]
final class UnlinkButton
{
    public User $user;

    /** The `app_friend_action` action: remove or block. */
    public string $action;

    public string $label;

    public string $icon;

    public string $question;

    /**
     * False renders the modal alone, opened by a menu entry elsewhere: a closed dropdown
     * hides its content with `display: none`, so the modal cannot live inside it.
     */
    public bool $withTrigger = true;
}
